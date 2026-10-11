<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Key;

use DateTimeImmutable;
use Beffroi\Core\Domain\Event\TenantKeyRotated;
use Beffroi\Core\Domain\Key\KeyAlgorithmInterface;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\LogicException;
use Beffroi\Core\Port\TenantKeyRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * The second step of the life of a key (architecture §3.3, OIDC Core §10.1.1): the staged key is the one
 * in use from now on, and the key it takes over from starts retiring so that what is bound to it stays
 * verifiable until its window ends. One event says both (TenantKeyRotated). The counterpart of KeyStaging,
 * KeySuccession and KeyRetirement.
 *
 * Four named acts: one staged key an operator names (`activate()`), the timed step of a staging window
 * (`activateDue()`), a rotation that stages and takes over in one call (`rotate()`, for the first key of a
 * tenant and for an operator who asks for one now), and the same for a compromised key
 * (`rotateAfterCompromise()`), whose retirement has no window at all and is asked of KeyRetirement.
 *
 * How long the key it takes over from stays published is never written here: it is read from the kinds of
 * artifact that name the use of that key (RetirementWindow).
 */
final readonly class KeyTakeOver
{
    public function __construct(
        private TenantKeyRepositoryInterface $keys,
        private NamedKey $named,
        private KeyStaging $staging,
        private RetirementWindow $window,
        private KeyRetirement $retirement,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * The staged key an operator names is the one in use from now on; the active one starts retiring.
     *
     * @throws KeyNotFoundException when the tenant holds no key of that identifier
     * @throws LogicException when the key is not staged
     */
    public function activate(Tenant $tenant, string $kid, RequestContext $context): TenantKey
    {
        return $this->takeOver($this->named->of($tenant, $kid), $context);
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
                $activated[] = $this->takeOver($key, $context);
            }
        }

        return $activated;
    }

    /**
     * Stages and takes over at once: the new key signs from now on and the previous one, if any, starts
     * its retirement window.
     */
    public function rotate(Tenant $tenant, KeyAlgorithmInterface $algorithm, RequestContext $context): TenantKey
    {
        return $this->takeOver($this->staging->stageDueAtOnce($tenant, $algorithm, $context), $context);
    }

    /**
     * The same, for a key that is compromised: the previous one leaves the JWK Set in this call, with no
     * window, because what it signed is not to be honoured any more. Named rather than a flag on
     * `rotate()`: the two are different decisions and the trail tells them apart (RetirementReason).
     */
    public function rotateAfterCompromise(Tenant $tenant, KeyAlgorithmInterface $algorithm, RequestContext $context): TenantKey
    {
        $key = $this->staging->stageDueAtOnce($tenant, $algorithm, $context);
        $now = $this->clock->now();
        // looked up before the activation, or the new key would be among the candidates
        $previous = $this->keys->findActive($tenant, $algorithm);
        // no window at all: the retirement of a compromised key is due the moment it stops working
        $this->activateInPlaceOf($key, $previous, $now, $now, $context);
        if ($previous !== null) {
            $this->retirement->retireAfterCompromise($previous, $context);
        }

        return $key;
    }

    /**
     * The ordinary take-over, the one that leaves the key it replaces published for as long as what is
     * bound to it may still arrive.
     *
     * @throws LogicException when the key is not staged
     */
    private function takeOver(TenantKey $key, RequestContext $context): TenantKey
    {
        $now = $this->clock->now();
        // looked up before the activation, or the new key would be among the candidates
        $previous = $this->keys->findActive($key->tenant, $key->algorithm);
        $this->activateInPlaceOf($key, $previous, $now, $this->window->dueAt($key->tenant, $key->algorithm->keyUse(), $now), $context);

        return $key;
    }

    /**
     * The hand-over itself: the staged key activates, the key it replaces starts retiring until the date
     * the caller read, and one event says both. What becomes of that key afterwards is the caller's: a
     * window ends by itself (KeyRetirement::retireDue()), a compromise ends it in the same call.
     *
     * @throws LogicException when the key is not staged
     */
    private function activateInPlaceOf(
        TenantKey $key,
        ?TenantKey $previous,
        DateTimeImmutable $now,
        DateTimeImmutable $retirementDueAt,
        RequestContext $context,
    ): void {
        $key->activate($now);
        if ($previous !== null) {
            $previous->startRetiring($now, $retirementDueAt);
            $this->keys->save($previous);
        }
        $this->keys->save($key);
        $this->events->dispatch(new TenantKeyRotated($key, $previous, $now, $context));
    }
}
