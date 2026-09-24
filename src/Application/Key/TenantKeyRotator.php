<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Key;

use DateInterval;
use DateTimeImmutable;
use Heidi\Core\Domain\Event\TenantKeyRetired;
use Heidi\Core\Domain\Event\TenantKeyRotated;
use Heidi\Core\Domain\Event\TenantKeyStaged;
use Heidi\Core\Domain\Key\KeyAlgorithmInterface;
use Heidi\Core\Domain\Key\KeyStatus;
use Heidi\Core\Domain\Key\KeyUse;
use Heidi\Core\Domain\Key\RetirementReason;
use Heidi\Core\Domain\Key\TenantKey;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\KeyNotFoundException;
use Heidi\Core\Exception\LogicException;
use Heidi\Core\Exception\RetirementNotDueException;
use Heidi\Core\Port\KeyBoundArtifactInterface;
use Heidi\Core\Port\KeyPairGeneratorInterface;
use Heidi\Core\Port\KeySealerInterface;
use Heidi\Core\Port\TenantKeyRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function sprintf;

/**
 * Rotation without an outage (OIDC Core §10.1.1, architecture §3.3): stage() publishes a key before it is
 * used, activate() puts it to work and starts retiring the previous one, retireDue() removes a key once
 * everything bound to it has expired. rotate() does the first two steps at once; activateDue() does the
 * second one when the staging window has ended.
 *
 * The same four steps for a key that signs and for a key a client encrypts to: what changes between the
 * two is only how long the retirement window lasts, and that is read from the kinds of artifact that name
 * the use of the key retiring, never written here.
 */
final readonly class TenantKeyRotator
{
    /**
     * @param iterable<KeyBoundArtifactInterface> $artifacts the longest lifetime among those of the use of a key dates its retirement
     * @param DateInterval $stagingDelay how long a staged key stays published before it is used: what a relying party needs to see it
     */
    public function __construct(
        private TenantKeyRepositoryInterface $keys,
        private KeyPairGeneratorInterface $generator,
        private KeySealerInterface $sealer,
        private iterable $artifacts,
        private DateInterval $stagingDelay,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * Published, sealed, doing nothing until activate() or activateDue() once the staging delay elapsed.
     */
    public function stage(Tenant $tenant, KeyAlgorithmInterface $algorithm, RequestContext $context): TenantKey
    {
        $now = $this->clock->now();

        return $this->publish($tenant, $algorithm, $context, $now, $now->add($this->stagingDelay));
    }

    /**
     * The staged key is the one in use from now on; the active one starts retiring.
     */
    public function activate(Tenant $tenant, string $kid, RequestContext $context): TenantKey
    {
        return $this->takeOver($this->findOrFail($tenant, $kid), $context, compromised: false);
    }

    /**
     * The timed step of a staging window, idempotent.
     *
     * @return list<TenantKey> the keys activated by this call
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
     * Stages and activates at once: the new key signs from now on and the previous one, if any, starts
     * its retirement window.
     */
    public function rotate(Tenant $tenant, KeyAlgorithmInterface $algorithm, RequestContext $context): TenantKey
    {
        $now = $this->clock->now();

        return $this->takeOver($this->publish($tenant, $algorithm, $context, $now, $now), $context, compromised: false);
    }

    /**
     * The same, for a key that is compromised: the previous one leaves the JWK Set at once, with no
     * window, because what it signed is not to be honoured any more. Named rather than a flag on
     * rotate(): the two are different decisions and the trail tells them apart (RetirementReason).
     */
    public function rotateAfterCompromise(Tenant $tenant, KeyAlgorithmInterface $algorithm, RequestContext $context): TenantKey
    {
        $now = $this->clock->now();

        return $this->takeOver($this->publish($tenant, $algorithm, $context, $now, $now), $context, compromised: true);
    }

    /**
     * The timed step of a retirement window, idempotent.
     *
     * @return list<TenantKey> the keys retired by this call
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
            $this->events->dispatch(new TenantKeyRetired($key, RetirementReason::Due, $now, $context));
            $retired[] = $key;
        }

        return $retired;
    }

    /**
     * Before the retirement date the caller has to force it, and the trail says so.
     */
    public function retire(Tenant $tenant, string $kid, RequestContext $context, bool $force = false): TenantKey
    {
        $key = $this->findOrFail($tenant, $kid);
        if ($key->isActive()) {
            throw new LogicException('The active key cannot be retired; rotate first.');
        }
        $now = $this->clock->now();
        $due = $key->isDue($now);
        if ($key->status === KeyStatus::Retiring && ! $due && ! $force) {
            throw new RetirementNotDueException(sprintf(
                'The key "%s" is not due for retirement before %s: what it is bound to may still be alive.',
                $kid,
                $key->retirementDueAt?->format(DateTimeImmutable::RFC3339) ?? '?',
            ));
        }
        $key->retire($now);
        $this->keys->save($key);
        $this->events->dispatch(new TenantKeyRetired($key, $due ? RetirementReason::Due : RetirementReason::Forced, $now, $context));

        return $key;
    }

    private function publish(
        Tenant $tenant,
        KeyAlgorithmInterface $algorithm,
        RequestContext $context,
        DateTimeImmutable $now,
        DateTimeImmutable $activationDueAt,
    ): TenantKey {
        $pair = $this->generator->generate($algorithm);
        $key = new TenantKey($pair->kid, $tenant, $algorithm, $pair->publicJwk, $this->sealer->seal($tenant, $pair->privateJwk), $now, $activationDueAt);
        $this->keys->save($key);
        $this->events->dispatch(new TenantKeyStaged($key, $now, $context));

        return $key;
    }

    private function takeOver(TenantKey $key, RequestContext $context, bool $compromised): TenantKey
    {
        $now = $this->clock->now();
        // looked up before the activation, or the new key would be among the candidates
        $previous = $this->keys->findActive($key->tenant, $key->algorithm);
        $key->activate($now);
        if ($previous !== null) {
            $previous->startRetiring($now, $compromised ? $now : $this->retirementDue($key->tenant, $key->algorithm->keyUse(), $now));
            $this->keys->save($previous);
        }
        $this->keys->save($key);
        $this->events->dispatch(new TenantKeyRotated($key, $previous, $now, $context));
        if ($compromised && $previous !== null) {
            $previous->retire($now);
            $this->keys->save($previous);
            $this->events->dispatch(new TenantKeyRetired($previous, RetirementReason::Compromised, $now, $context));
        }

        return $key;
    }

    /**
     * The longest lifetime among the kinds of artifact bound to a key of that use: the JWS the tenant
     * issues for a key that signs, what a client may still be encrypting to it for a key it publishes to
     * be encrypted to. None of them means at once.
     */
    private function retirementDue(Tenant $tenant, KeyUse $use, DateTimeImmutable $retiringAt): DateTimeImmutable
    {
        $due = $retiringAt;
        foreach ($this->artifacts as $kind) {
            if ($kind->keyUse() !== $use) {
                continue;
            }
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

    private function findOrFail(Tenant $tenant, string $kid): TenantKey
    {
        return $this->keys->find($tenant, $kid) ?? throw new KeyNotFoundException(sprintf('Tenant "%s" has no key "%s".', $tenant->slug, $kid));
    }
}
