<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Authentication;

use DateInterval;
use Heidi\Core\Domain\Event\UserAuthenticated;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Session\AuthenticationMethod;
use Heidi\Core\Domain\Session\AuthenticationProofs;
use Heidi\Core\Domain\Session\AuthenticationSession;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Port\AuthenticationSessionRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Opens one session on what was just proven and says so; every opened session leaves the same trace,
 * whatever proved it (a password, a passkey, an upstream provider). The counterpart of SessionTerminator.
 * The lifetime policy is the one place it lives; how a session is extended is the caller's (touch()).
 */
final readonly class SessionOpener
{
    public function __construct(
        private AuthenticationSessionRepositoryInterface $sessions,
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
            Uuid::v7(),
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
