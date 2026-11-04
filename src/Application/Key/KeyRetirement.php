<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Key;

use DateTimeImmutable;
use Beffroi\Core\Domain\Event\TenantKeyRetired;
use Beffroi\Core\Domain\Key\KeyStatus;
use Beffroi\Core\Domain\Key\RetirementReason;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\LogicException;
use Beffroi\Core\Exception\RetirementNotDueException;
use Beffroi\Core\Port\TenantKeyRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function sprintf;

final readonly class KeyRetirement
{
    public function __construct(
        private TenantKeyRepositoryInterface $keys,
        private NamedKey $named,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * @return list<TenantKey>
     */
    public function retireDue(Tenant $tenant, RequestContext $context): array
    {
        $now = $this->clock->now();
        $retired = [];
        foreach ($this->keys->findInService($tenant) as $key) {
            if (! $key->isDue($now)) {
                continue;
            }
            $this->leave($key, RetirementReason::Due, $now, $context);
            $retired[] = $key;
        }

        return $retired;
    }

    /**
     * @throws KeyNotFoundException
     * @throws LogicException when the key is the active one, or is not retiring
     * @throws RetirementNotDueException
     */
    public function retire(Tenant $tenant, string $kid, RequestContext $context): TenantKey
    {
        $key = $this->retiringKey($tenant, $kid);
        $now = $this->clock->now();
        if ($key->status === KeyStatus::Retiring && ! $key->isDue($now)) {
            throw new RetirementNotDueException(sprintf(
                'The key "%s" is not due for retirement before %s: what it is bound to may still be alive.',
                $kid,
                $key->retirementDueAt?->format(DateTimeImmutable::RFC3339) ?? '?',
            ));
        }
        $this->leave($key, RetirementReason::Due, $now, $context);

        return $key;
    }

    /**
     * @throws KeyNotFoundException
     * @throws LogicException when the key is the active one, or is not retiring
     */
    public function retireBeforeItsDate(Tenant $tenant, string $kid, RequestContext $context): TenantKey
    {
        $key = $this->retiringKey($tenant, $kid);
        $now = $this->clock->now();
        $this->leave($key, $key->isDue($now) ? RetirementReason::Due : RetirementReason::Forced, $now, $context);

        return $key;
    }

    /**
     * @throws LogicException when the key is not retiring
     */
    public function retireAfterCompromise(TenantKey $compromised, RequestContext $context): void
    {
        $this->leave($compromised, RetirementReason::Compromised, $this->clock->now(), $context);
    }

    /**
     * @throws KeyNotFoundException
     * @throws LogicException
     */
    private function retiringKey(Tenant $tenant, string $kid): TenantKey
    {
        $key = $this->named->of($tenant, $kid);
        if ($key->isActive()) {
            throw new LogicException('The active key cannot be retired; rotate first.');
        }

        return $key;
    }

    /**
     * @throws LogicException when the key is not retiring
     */
    private function leave(TenantKey $key, RetirementReason $reason, DateTimeImmutable $now, RequestContext $context): void
    {
        $key->retire($now);
        $this->keys->save($key);
        $this->events->dispatch(new TenantKeyRetired($key, $reason, $now, $context));
    }
}
