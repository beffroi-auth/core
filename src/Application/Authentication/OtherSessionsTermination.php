<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use Beffroi\Core\Domain\Session\AuthenticationSession;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Session\SessionEnd;
use Beffroi\Core\Port\AuthenticationSessionRepositoryInterface;
use Psr\Clock\ClockInterface;

/**
 * Every session of a person but the one they are reading from (architecture §5.2): what somebody does
 * when they have signed in somewhere they should not have, or on a machine they no longer hold, and what
 * they would otherwise do one line at a time.
 *
 * **It ends them one by one, through SessionTerminator.** Each one leaves the trace every ended session
 * leaves and reaches the relying parties it opened for by the back-channel, because the end of a session
 * is one act of the core: a statement that closed them all at once would be the one end nobody is told
 * about.
 *
 * **The session it is given is the one it keeps**, and it is the caller's own: the person reading the page
 * is not signed out by the button that signs out everything else.
 */
final readonly class OtherSessionsTermination
{
    public function __construct(
        private AuthenticationSessionRepositoryInterface $sessions,
        private SessionTerminator $terminator,
        private ClockInterface $clock,
    ) {
    }

    /**
     * Says how many it ended, so that the page can tell a person who had five browsers open from a person
     * who had none.
     */
    public function endEveryOther(AuthenticationSession $kept, RequestContext $context): int
    {
        $ended = 0;
        foreach ($this->sessions->findActiveByUser($kept->user, $this->clock->now()) as $session) {
            if ($session->id->equals($kept->id)) {
                continue;
            }
            $this->terminator->end($session, SessionEnd::User, $context);
            ++$ended;
        }

        return $ended;
    }
}
