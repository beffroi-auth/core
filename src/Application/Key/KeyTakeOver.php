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
 * OIDC Core §10.1.1
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
     * @throws KeyNotFoundException
     * @throws LogicException when the key is not staged
     */
    public function activate(Tenant $tenant, string $kid, RequestContext $context): TenantKey
    {
        return $this->takeOver($this->named->of($tenant, $kid), $context);
    }

    /**
     * @return list<TenantKey>
     */
    public function activateDue(Tenant $tenant, RequestContext $context): array
    {
        $now = $this->clock->now();
        $activated = [];
        foreach ($this->keys->findInService($tenant) as $key) {
            if (! $key->isDueForActivation($now)) {
                continue;
            }
            $active = $this->keys->findActive($tenant, $key->algorithm);
            if ($active !== null && $key->wasCreatedBefore($active)) {
                $this->leaveUnused($key, $now);

                continue;
            }
            $activated[] = $this->takeOver($key, $context);
        }

        return $activated;
    }

    public function rotate(Tenant $tenant, KeyAlgorithmInterface $algorithm, RequestContext $context): TenantKey
    {
        return $this->takeOver($this->staging->stageDueAtOnce($tenant, $algorithm, $context), $context);
    }

    public function rotateAfterCompromise(Tenant $tenant, KeyAlgorithmInterface $algorithm, RequestContext $context): TenantKey
    {
        // Looked up before the new key is staged, or it would be among them.
        $exposed = $this->exposed($tenant, $algorithm);
        $key = $this->staging->stageDueAtOnce($tenant, $algorithm, $context);
        $now = $this->clock->now();
        // Looked up before the activation, or the new key would be among the candidates.
        $previous = $this->keys->findActive($tenant, $algorithm);
        // No window: a compromised key is due for retirement the moment it stops working.
        $this->activateInPlaceOf($key, $previous, $now, $now, $context);
        if ($previous !== null) {
            $this->retirement->retireAfterCompromise($previous, $context);
        }
        // What exposed the active key exposed the keys that wait and the keys that retire: none keeps a window.
        foreach ($exposed as $other) {
            if ($other->isStaged()) {
                $other->startRetiring($now, $now);
            }
            $this->retirement->retireAfterCompromise($other, $context);
        }

        return $key;
    }

    /**
     * @throws LogicException when the key is not staged
     */
    private function takeOver(TenantKey $key, RequestContext $context): TenantKey
    {
        $now = $this->clock->now();
        // Looked up before the activation, or the new key would be among the candidates.
        $previous = $this->keys->findActive($key->tenant, $key->algorithm);
        $this->activateInPlaceOf($key, $previous, $now, $this->window->dueAt($key->tenant, $key->algorithm->keyUse(), $now), $context);

        return $key;
    }

    /**
     * @return list<TenantKey> the keys of the algorithm in service besides the active one
     */
    private function exposed(Tenant $tenant, KeyAlgorithmInterface $algorithm): array
    {
        $exposed = [];
        foreach ($this->keys->findInService($tenant) as $key) {
            if (! $key->isActive() && $key->algorithm->name() === $algorithm->name()) {
                $exposed[] = $key;
            }
        }

        return $exposed;
    }

    /**
     * A newer key took over while this one waited: it never signs, and stays published for what a client may
     * have encrypted to it, as a key that was replaced does.
     */
    private function leaveUnused(TenantKey $key, DateTimeImmutable $now): void
    {
        $key->startRetiring($now, $this->window->dueAt($key->tenant, $key->algorithm->keyUse(), $now));
        $this->keys->save($key);
    }

    /**
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
