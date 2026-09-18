<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Key;

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
 * Rotation without an outage, as OIDC Core §10.1.1 describes it for relying parties that cache the JWK
 * Set: the new key is published before it signs (stage(), then activate() once every cache can have seen
 * it), the previous one stays published while a JWS it signed can be alive (its retirement date, computed
 * from the kinds of JWS the tenant issues), then leaves the set when that date has passed (retireDue()).
 * rotate() does the two steps at once, for the first key of a tenant (nobody caches an empty JWK Set) or
 * for an operator who knows every client is cold; with "compromised", the previous key is retired with
 * no window, and every JWS naming it is refused from then on.
 */
final readonly class SigningKeyRotator
{
    /**
     * @param iterable<IssuedJwsKindInterface> $issuedJws every kind of JWS the tenant signs; the longest lifetime among them dates a retirement
     */
    public function __construct(
        private SigningKeyRepositoryInterface $keys,
        private KeyPairGeneratorInterface $generator,
        private KeySealerInterface $sealer,
        private iterable $issuedJws,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * Generates a key, seals it, publishes it: it signs nothing until activate().
     */
    public function stage(Tenant $tenant, SignatureAlgorithm $algorithm, RequestContext $context): SigningKey
    {
        $now = $this->clock->now();
        $pair = $this->generator->generate($algorithm);
        $key = new SigningKey($pair->kid, $tenant, $algorithm, $pair->publicJwk, $this->sealer->seal($tenant, $pair->privateJwk), $now);
        $this->keys->save($key);
        $this->events->dispatch(new SigningKeyStaged($key, $now, $context));

        return $key;
    }

    /**
     * A staged key signs from now on; the key that was active starts retiring, due once the last JWS it
     * could have signed has expired.
     */
    public function activate(Tenant $tenant, string $kid, RequestContext $context): SigningKey
    {
        return $this->takeOver($this->findOrFail($tenant, $kid), $context, compromised: false);
    }

    /**
     * Stages and activates at once. With "compromised", the previous active key is retired at once as well:
     * no window, every JWS naming its kid refused from then on.
     */
    public function rotate(Tenant $tenant, SignatureAlgorithm $algorithm, RequestContext $context, bool $compromised = false): SigningKey
    {
        return $this->takeOver($this->stage($tenant, $algorithm, $context), $context, $compromised);
    }

    /**
     * Retires every key of the tenant whose retirement date has passed, and none other: this is the timed
     * step of a rotation, safe to run as often as wanted.
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
     * Retires one retiring key. Before its retirement date, the caller has to force it, and the trail says
     * so; the active key cannot be retired, rotate first.
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
        // A staged or a retired key is refused by the key itself.
        $key->retire($now);
        $this->keys->save($key);
        $this->events->dispatch(new SigningKeyRetired($key, $due ? RetirementReason::Due : RetirementReason::Forced, $now, $context));

        return $key;
    }

    private function takeOver(SigningKey $key, RequestContext $context, bool $compromised): SigningKey
    {
        $now = $this->clock->now();
        // Looked up before the activation: in memory, the new key would otherwise be among the candidates.
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
     * When the last JWS a key retiring now could have signed has expired: the longest lifetime among the
     * kinds the tenant issues. A tenant that issues no JWS retires its key at once.
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
