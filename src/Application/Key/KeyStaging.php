<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Key;

use DateTimeImmutable;
use Beffroi\Core\Domain\Event\TenantKeyStaged;
use Beffroi\Core\Domain\Key\KeyAlgorithmInterface;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\LogicException;
use Beffroi\Core\Port\KeyPairGeneratorInterface;
use Beffroi\Core\Port\KeySealerInterface;
use Beffroi\Core\Port\RotationSchedulesInterface;
use Beffroi\Core\Port\TenantKeyRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * OIDC Core §10.1.1
 */
final readonly class KeyStaging
{
    public function __construct(
        private TenantKeyRepositoryInterface $keys,
        private KeyPairGeneratorInterface $generator,
        private KeySealerInterface $sealer,
        private RotationSchedulesInterface $schedules,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * @throws LogicException when no family holds the algorithm
     */
    public function stage(Tenant $tenant, KeyAlgorithmInterface $algorithm, RequestContext $context): TenantKey
    {
        $now = $this->clock->now();

        return $this->publish($tenant, $algorithm, $context, $now, $now->add($this->schedules->of($algorithm)->stagingDelay));
    }

    public function stageDueAtOnce(Tenant $tenant, KeyAlgorithmInterface $algorithm, RequestContext $context): TenantKey
    {
        $now = $this->clock->now();

        return $this->publish($tenant, $algorithm, $context, $now, $now);
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
}
