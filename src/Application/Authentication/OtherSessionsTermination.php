<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationSession;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Session\SessionEnd;
use Beffroi\Core\Port\CanFindUnendedSessionsInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Ends the sessions of a person, every one or every one but the session kept, each through SessionTerminator, never in
 * bulk, so that each leaves its trace and its back-channel logout. Expired sessions are ended too: expiry ends nothing,
 * and their refresh tokens would live on.
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
        return $this->endEach($kept->user, $kept->id, $context);
    }

    public function endEvery(User $user, RequestContext $context): int
    {
        return $this->endEach($user, null, $context);
    }

    private function endEach(User $user, ?Uuid $kept, RequestContext $context): int
    {
        $ended = 0;
        foreach ($this->sessions->findUnendedByUser($user) as $session) {
            if ($kept !== null && $session->id->equals($kept)) {
                continue;
            }
            $this->terminator->end($session, SessionEnd::User, $context);
            ++$ended;
        }

        return $ended;
    }
}
