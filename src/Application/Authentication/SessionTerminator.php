<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Authentication;

use Heidi\Core\Domain\Event\AuthenticationSessionEnded;
use Heidi\Core\Domain\Session\AuthenticationSession;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Session\SessionEnd;
use Heidi\Core\Port\AuthenticationSessionRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Ends one session and says so. Logout, an administrator, the expiry sweep and a security response all go
 * through here, so that every ended session leaves the same trace.
 */
final readonly class SessionTerminator
{
    public function __construct(
        private AuthenticationSessionRepositoryInterface $sessions,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    public function end(AuthenticationSession $session, SessionEnd $by, RequestContext $context): void
    {
        if ($session->hasEnded()) {
            return;
        }
        $now = $this->clock->now();
        $session->end($by, $now);
        $this->sessions->save($session);
        $this->events->dispatch(new AuthenticationSessionEnded($session, $now, $context));
    }
}
