<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use DateInterval;
use DateTimeImmutable;
use Beffroi\Core\Domain\Event\AuthenticationFailureReason;
use Beffroi\Core\Domain\Event\UserAuthenticated;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationMethod;
use Beffroi\Core\Domain\Session\AuthenticationProofs;
use Beffroi\Core\Domain\Session\AuthenticationSession;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\AuthenticationSessionRepositoryInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final readonly class SessionOpener
{
    public function __construct(
        private AuthenticationSessionRepositoryInterface $sessions,
        private UuidGeneratorInterface $uuids,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
        private AuthenticationRefusal $refusals,
        private DateInterval $lifetime = new DateInterval('PT12H'),
    ) {
    }

    /**
     * @param non-empty-list<AuthenticationMethod> $methods
     *
     * @throws AuthenticationFailedException when what was proven is under the floor of the tenant
     */
    public function open(
        Tenant $tenant,
        User $user,
        array $methods,
        RequestContext $context,
        ?DateTimeImmutable $provenAt = null,
        ?DateTimeImmutable $notOnOrAfter = null,
    ): AuthenticationSession {
        if (! $tenant->loginFloor->isSatisfiedBy($methods)) {
            throw $this->refusals->refuse($tenant, $context, AuthenticationFailureReason::BelowTheFloor, $methods[0], null, $user);
        }
        $now = $this->clock->now();
        $expiresAt = $now->add($this->lifetime);
        $session = new AuthenticationSession(
            $this->uuids->timeOrdered(),
            $tenant,
            $user,
            $now,
            AuthenticationProofs::ofAll($methods, $provenAt === null || $provenAt > $now ? $now : $provenAt),
            $context,
            $notOnOrAfter !== null && $notOnOrAfter < $expiresAt ? $notOnOrAfter : $expiresAt,
        );
        $this->sessions->save($session);
        $this->events->dispatch(new UserAuthenticated($session));

        return $session;
    }
}
