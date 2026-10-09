<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Key;

use DateInterval;
use DateTimeImmutable;
use Beffroi\Core\Domain\Event\TenantKeyRetired;
use Beffroi\Core\Domain\Event\TenantKeyRotated;
use Beffroi\Core\Domain\Event\TenantKeyStaged;
use Beffroi\Core\Domain\Key\KeyAlgorithmInterface;
use Beffroi\Core\Domain\Key\KeyStatus;
use Beffroi\Core\Domain\Key\KeyUse;
use Beffroi\Core\Domain\Key\RetirementReason;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\LogicException;
use Beffroi\Core\Exception\RetirementNotDueException;
use Beffroi\Core\Port\KeyBoundArtifactInterface;
use Beffroi\Core\Port\KeyPairGeneratorInterface;
use Beffroi\Core\Port\KeySealerInterface;
use Beffroi\Core\Port\TenantKeyRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function sprintf;

/**
 * Rotation without an outage (OIDC Core §10.1.1, architecture §3.3): stage() publishes a key before it is
 * used, activate() puts it to work and starts retiring the previous one, retireDue() removes a key once
 * everything bound to it has expired. rotate() does the first two steps at once; activateDue() does the
 * second one when the staging window has ended, and stageSuccessorsDue() is what starts a rotation nobody
 * asked for, when a key has worked for the age a deployment gives one (issue #455).
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
     * @param DateInterval $age how long a key works before a successor is staged for it, counted from its activation
     */
    public function __construct(
        private TenantKeyRepositoryInterface $keys,
        private KeyPairGeneratorInterface $generator,
        private KeySealerInterface $sealer,
        private iterable $artifacts,
        private DateInterval $stagingDelay,
        private DateInterval $age,
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
        foreach ($this->keys->findInService($tenant) as $key) {
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
     * The timed step that starts a rotation nobody asked for (issue #455): an active key that has worked
     * for the age of a key gets a successor of its own algorithm, staged and not activated.
     *
     * **Staged and not rotated**, because that is the whole point of the staging window: a relying party
     * sees the new "kid" in the JWK Set before anything is signed with it, and the two steps that already
     * exist finish the work, activateDue() when the window ends and retireDue() when the old key has
     * nothing left alive. A step that rotated would make a rotation without an outage out of a rotation
     * nobody watched.
     *
     * **Idempotent by what it looks at and not by a flag**: a key gets a successor only when its
     * algorithm has no staged key, so the tick that follows finds the one this call staged and stages
     * nothing. The reading is one pass over the keys in service, the staged ones included.
     *
     * @return list<TenantKey> the successors staged by this call
     *
     * @throws LogicException if the age is not longer than the retirement window of what the key is for:
     *                        each successor would then start retiring before the previous key had
     *                        retired, and the JWK Set would grow with every rotation
     */
    public function stageSuccessorsDue(Tenant $tenant, RequestContext $context): array
    {
        $now = $this->clock->now();
        $inService = $this->keys->findInService($tenant);
        $awaited = [];
        foreach ($inService as $key) {
            if ($key->isStaged()) {
                $awaited[$key->algorithm->name()] = true;
            }
        }
        $staged = [];
        foreach ($inService as $key) {
            if (! $key->hasReachedItsAge($now, $this->age) || isset($awaited[$key->algorithm->name()])) {
                continue;
            }
            $this->assertTheAgeOutlasts($tenant, $key, $now);
            $staged[] = $this->publish($tenant, $key->algorithm, $context, $now, $now->add($this->stagingDelay));
            $awaited[$key->algorithm->name()] = true;
        }

        return $staged;
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
        foreach ($this->keys->findInService($tenant) as $key) {
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

    /**
     * The age has to outlast what a key of that use holds back, and the comparison is made here because
     * here is the one place both numbers exist: the age is the deployment's and the window is the tenant's,
     * read from the kinds of artifact that name the use. A deployment that got it wrong hears it on the
     * first tick rather than finding its JWK Set grown a quarter later.
     *
     * @throws LogicException
     */
    private function assertTheAgeOutlasts(Tenant $tenant, TenantKey $key, DateTimeImmutable $now): void
    {
        $window = $this->retirementDue($tenant, $key->algorithm->keyUse(), $now);
        if ($now->add($this->age) > $window) {
            return;
        }
        throw new LogicException(sprintf(
            'A key of the "%s" use works for %s and what it holds back lives until %s: the age of a key is longer than that window, or every rotation leaves a key behind in the JWK Set.',
            $key->algorithm->keyUse()->value,
            $now->add($this->age)->format(DateTimeImmutable::RFC3339),
            $window->format(DateTimeImmutable::RFC3339),
        ));
    }

    private function findOrFail(Tenant $tenant, string $kid): TenantKey
    {
        return $this->keys->find($tenant, $kid) ?? throw new KeyNotFoundException(sprintf('Tenant "%s" has no key "%s".', $tenant->slug, $kid));
    }
}
