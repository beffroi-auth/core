<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use DateInterval;
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

/**
 * Opens one session on what was just proven and says so; every opened session leaves the same trace,
 * whatever proved it (a password, a passkey, an upstream provider). The counterpart of SessionTerminator.
 * The lifetime policy is the one place it lives; how a session is extended is the caller's (touch()).
 */
final readonly class SessionOpener
{
    public function __construct(
        private AuthenticationSessionRepositoryInterface $sessions,
        private UuidGeneratorInterface $uuids,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
        private DateInterval $lifetime = new DateInterval('PT12H'),
    ) {
    }

    /**
     * @param non-empty-list<AuthenticationMethod> $methods what was proven, every one dated now
     */
    public function open(Tenant $tenant, User $user, array $methods, RequestContext $context): AuthenticationSession
    {
        $now = $this->clock->now();
        $session = new AuthenticationSession(
            $this->uuids->timeOrdered(),
            $tenant,
            $user,
            $now,
            AuthenticationProofs::ofAll($methods, $now),
            $context,
            $now->add($this->lifetime),
        );
        $this->sessions->save($session);
        $this->events->dispatch(new UserAuthenticated($session));

        return $session;
    }
}
