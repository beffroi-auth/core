<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use Beffroi\Core\Domain\Session\AuthenticationSession;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Session\SessionEnd;
use Beffroi\Core\Port\CanFindUnendedSessionsInterface;

/**
 * Ends each session through SessionTerminator, never in bulk, so that each leaves its trace and its back-channel
 * logout. Expired sessions are ended too: expiry ends nothing, and their refresh tokens would live on.
 */
final readonly class OtherSessionsTermination
{
    public function __construct(
        private CanFindUnendedSessionsInterface $sessions,
        private SessionTerminator $terminator,
    ) {
    }

    public function endEveryOther(AuthenticationSession $kept, RequestContext $context): int
    {
        $ended = 0;
        foreach ($this->sessions->findUnendedByUser($kept->user) as $session) {
            if ($session->id->equals($kept->id)) {
                continue;
            }
            $this->terminator->end($session, SessionEnd::User, $context);
            ++$ended;
        }

        return $ended;
    }
}
