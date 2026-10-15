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
 * The first step of the life of a key (architecture §3.3, OIDC Core §10.1.1): the pair is generated in the
 * process, its private half is sealed before anything is written, and the key is published doing nothing.
 * The counterpart of KeyTakeOver, KeySuccession and KeyRetirement.
 *
 * **Two named acts and no flag between them.** A key staged for its window is seen by a relying party
 * before anything is signed with it, which is what makes a rotation without an outage; a key staged to
 * take over at once is what the first key of a tenant needs, nobody caching an empty JWK Set, and what an
 * operator who knows every relying party is cold asks for. Both publish the same way and say so with the
 * same event: when the key takes over is the whole difference, and it is a date on the key.
 *
 * The same first step for a key that signs and for a key a client encrypts to: what the key is for is what
 * its algorithm is for, and nothing here reads it.
 *
 * **How long the window lasts is the family's** (issue #738): read from the schedule of the family that
 * holds the algorithm, so that a key a SAML service provider must find in metadata it reads once a day waits
 * days, and a key of the JWK Set minutes. Nothing here holds a delay of its own.
 */
final readonly class KeyStaging
{
    /**
     * @param RotationSchedulesInterface $schedules how long a staged key of each family stays published before it is used: what its readers need to see it
     */
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
     * Published, sealed, doing nothing until the staging window of its family ends and the take-over
     * activates it.
     *
     * @throws LogicException when no family holds the algorithm
     */
    public function stage(Tenant $tenant, KeyAlgorithmInterface $algorithm, RequestContext $context): TenantKey
    {
        $now = $this->clock->now();

        return $this->publish($tenant, $algorithm, $context, $now, $now->add($this->schedules->of($algorithm)->stagingDelay));
    }

    /**
     * The same key, due to take over the moment it is published: there is no window to wait for.
     */
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
