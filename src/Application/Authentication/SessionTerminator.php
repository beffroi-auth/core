<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use Beffroi\Core\Domain\Event\AuthenticationSessionEnded;
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
        if ($session->hasEnded()) {
            return;
        }
        $now = $this->clock->now();
        $session->end($by, $now);
        $this->sessions->save($session);
        $this->events->dispatch(new AuthenticationSessionEnded($session, $now, $context));
    }
}
