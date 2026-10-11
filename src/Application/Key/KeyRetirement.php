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

/**
 * The last step of the life of a key (architecture §3.3): the key leaves the JWK Set, and a JWS that names
 * its "kid" is refused from then on. The counterpart of KeyStaging, KeyTakeOver and KeySuccession.
 *
 * **One act per decision, and the trail tells them apart** (RetirementReason), rather than one method that
 * chooses between them on a flag: the date does it (`retireDue()`, the timed step, and `retire()` for one
 * key an operator names), an operator does it before the date when nothing bound to the key can arrive any
 * more (`retireBeforeItsDate()`), and a compromise does it with no window at all
 * (`retireAfterCompromise()`, which the take-over asks for in the call that replaced the key).
 *
 * The active key is never retired here, whatever the act: a tenant without an active key signs nothing, so
 * a rotation comes first.
 */
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
            $this->leave($key, RetirementReason::Due, $now, $context);
            $retired[] = $key;
        }

        return $retired;
    }

    /**
     * One key an operator names, past its retirement date.
     *
     * @throws KeyNotFoundException when the tenant holds no key of that identifier
     * @throws LogicException when the key is the active one, or is not retiring
     * @throws RetirementNotDueException before the date: what the key is bound to may still be alive
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
     * The same key before its retirement date, for an operator who knows that everything bound to it has
     * expired, or who wants it out after a compromise. Named rather than a flag on `retire()`: the two are
     * different decisions and the trail says which one was taken. A key that turns out to be past its date
     * is still retired as due, the trail stating what was the case and not what was asked.
     *
     * @throws KeyNotFoundException when the tenant holds no key of that identifier
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
     * The key a compromise took out of service, with no window at all: what it signed is not to be
     * honoured any more, so it leaves the JWK Set in the call that replaced it (KeyTakeOver).
     *
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
