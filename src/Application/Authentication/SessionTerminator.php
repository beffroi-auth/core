<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use Beffroi\Core\Domain\Event\AuthenticationSessionEnded;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\AuthenticationSession;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Session\SessionEnd;
use Beffroi\Core\Port\AuthenticationSessionRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

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
        $this->endOnce($session, $by, $context, null);
    }

    /**
     * The act of the end names the administrator of the tenant who ended the session (decision 0038).
     */
    public function endByAnAdministrator(AuthenticationSession $session, Subject $administrator, RequestContext $context): void
    {
        $this->endOnce($session, SessionEnd::Administrator, $context, $administrator);
    }

    private function endOnce(AuthenticationSession $session, SessionEnd $by, RequestContext $context, ?Subject $administrator): void
    {
        if ($session->hasEnded()) {
            return;
        }
        $now = $this->clock->now();
        $session->end($by, $now);
        $this->sessions->save($session);
        $this->events->dispatch(new AuthenticationSessionEnded($session, $now, $context, $administrator));
    }
}
