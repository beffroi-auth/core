<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Key;

use DateInterval;
use DateTimeImmutable;
use Heidi\Core\Domain\Event\SigningKeyRetired;
use Heidi\Core\Domain\Event\SigningKeyRotated;
use Heidi\Core\Domain\Event\SigningKeyStaged;
use Heidi\Core\Domain\Key\KeyStatus;
use Heidi\Core\Domain\Key\RetirementReason;
use Heidi\Core\Domain\Key\SignatureAlgorithm;
use Heidi\Core\Domain\Key\SigningKey;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\KeyNotFoundException;
use Heidi\Core\Exception\LogicException;
use Heidi\Core\Exception\RetirementNotDueException;
use Heidi\Core\Port\IssuedJwsKindInterface;
use Heidi\Core\Port\KeyPairGeneratorInterface;
use Heidi\Core\Port\KeySealerInterface;
use Heidi\Core\Port\SigningKeyRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function sprintf;

/**
 * Rotation without an outage (OIDC Core §10.1.1, architecture §3.3): stage() publishes a key before it
 * signs, activate() makes it sign and starts retiring the previous one, retireDue() removes a key once
 * every JWS it could have signed has expired. rotate() does the first two steps at once; activateDue()
 * does the second one when the staging window has ended.
 */
final readonly class SigningKeyRotator
{
    /**
     * @param iterable<IssuedJwsKindInterface> $issuedJws the longest lifetime among them dates a retirement
     * @param DateInterval $stagingDelay how long a staged key stays published before it signs: what a relying party needs to see it
     */
    public function __construct(
        private SigningKeyRepositoryInterface $keys,
        private KeyPairGeneratorInterface $generator,
        private KeySealerInterface $sealer,
        private iterable $issuedJws,
        private DateInterval $stagingDelay,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * Published, sealed, signing nothing until activate() or activateDue() once the staging delay elapsed.
     */
    public function stage(Tenant $tenant, SignatureAlgorithm $algorithm, RequestContext $context): SigningKey
    {
        $now = $this->clock->now();

        return $this->publish($tenant, $algorithm, $context, $now, $now->add($this->stagingDelay));
    }

    /**
     * The staged key signs from now on; the active one starts retiring.
     */
    public function activate(Tenant $tenant, string $kid, RequestContext $context): SigningKey
    {
        return $this->takeOver($this->findOrFail($tenant, $kid), $context, compromised: false);
    }

    /**
     * The timed step of a staging window, idempotent.
     *
     * @return list<SigningKey> the keys activated by this call
     */
    public function activateDue(Tenant $tenant, RequestContext $context): array
    {
        $now = $this->clock->now();
        $activated = [];
        foreach ($this->keys->findPublished($tenant) as $key) {
            if ($key->isDueForActivation($now)) {
                $activated[] = $this->takeOver($key, $context, compromised: false);
            }
        }

        return $activated;
    }

    /**
     * Stages and activates at once; "compromised" retires the previous key with no window.
     */
    public function rotate(Tenant $tenant, SignatureAlgorithm $algorithm, RequestContext $context, bool $compromised = false): SigningKey
    {
        $now = $this->clock->now();

        return $this->takeOver($this->publish($tenant, $algorithm, $context, $now, $now), $context, $compromised);
    }

    /**
     * The timed step of a retirement window, idempotent.
     *
     * @return list<SigningKey> the keys retired by this call
     */
    public function retireDue(Tenant $tenant, RequestContext $context): array
    {
        $now = $this->clock->now();
        $retired = [];
        foreach ($this->keys->findPublished($tenant) as $key) {
            if (! $key->isDue($now)) {
                continue;
            }
            $key->retire($now);
            $this->keys->save($key);
            $this->events->dispatch(new SigningKeyRetired($key, RetirementReason::Due, $now, $context));
            $retired[] = $key;
        }

        return $retired;
    }

    /**
     * Before the retirement date the caller has to force it, and the trail says so.
     */
    public function retire(Tenant $tenant, string $kid, RequestContext $context, bool $force = false): SigningKey
    {
        $key = $this->findOrFail($tenant, $kid);
        if ($key->isActive()) {
            throw new LogicException('The active key cannot be retired; rotate first.');
        }
        $now = $this->clock->now();
        $due = $key->isDue($now);
        if ($key->status === KeyStatus::Retiring && ! $due && ! $force) {
            throw new RetirementNotDueException(sprintf('The key "%s" is not due for retirement before %s: a JWS it signed may be alive.', $kid, $key->retirementDueAt?->format(DateTimeImmutable::RFC3339) ?? '?'));
        }
        $key->retire($now);
        $this->keys->save($key);
        $this->events->dispatch(new SigningKeyRetired($key, $due ? RetirementReason::Due : RetirementReason::Forced, $now, $context));

        return $key;
    }

    private function publish(Tenant $tenant, SignatureAlgorithm $algorithm, RequestContext $context, DateTimeImmutable $now, DateTimeImmutable $activationDueAt): SigningKey
    {
        $pair = $this->generator->generate($algorithm);
        $key = new SigningKey($pair->kid, $tenant, $algorithm, $pair->publicJwk, $this->sealer->seal($tenant, $pair->privateJwk), $now, $activationDueAt);
        $this->keys->save($key);
        $this->events->dispatch(new SigningKeyStaged($key, $now, $context));

        return $key;
    }

    private function takeOver(SigningKey $key, RequestContext $context, bool $compromised): SigningKey
    {
        $now = $this->clock->now();
        // looked up before the activation, or the new key would be among the candidates
        $previous = $this->keys->findActive($key->tenant, $key->algorithm);
        $key->activate($now);
        if ($previous !== null) {
            $previous->startRetiring($now, $compromised ? $now : $this->retirementDue($key->tenant, $now));
            $this->keys->save($previous);
        }
        $this->keys->save($key);
        $this->events->dispatch(new SigningKeyRotated($key, $previous, $now, $context));
        if ($compromised && $previous !== null) {
            $previous->retire($now);
            $this->keys->save($previous);
            $this->events->dispatch(new SigningKeyRetired($previous, RetirementReason::Compromised, $now, $context));
        }

        return $key;
    }

    /**
     * The longest lifetime among the kinds of JWS the tenant issues; none means at once.
     */
    private function retirementDue(Tenant $tenant, DateTimeImmutable $retiringAt): DateTimeImmutable
    {
        $due = $retiringAt;
        foreach ($this->issuedJws as $kind) {
            $lifetime = $kind->longestLifetime($tenant);
            if ($lifetime === null) {
                continue;
            }
            $until = $retiringAt->add($lifetime);
            if ($until > $due) {
                $due = $until;
            }
        }

        return $due;
    }

    private function findOrFail(Tenant $tenant, string $kid): SigningKey
    {
        return $this->keys->find($tenant, $kid) ?? throw new KeyNotFoundException(sprintf('Tenant "%s" has no key "%s".', $tenant->slug, $kid));
    }
}
