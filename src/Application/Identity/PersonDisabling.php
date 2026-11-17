<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Application\Authentication\OtherSessionsTermination;
use Beffroi\Core\Domain\Event\PersonDisabled;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Port\UserRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * A person who can no longer sign in. The account is disabled before any session ends, so that none opens meanwhile;
 * then every session of theirs ends, its clients told by back-channel logout and its refresh tokens revoked. A person
 * disabled already is left as they are, and nothing is announced.
 */
final readonly class PersonDisabling
{
    public function __construct(
        private UserRepositoryInterface $users,
        private OtherSessionsTermination $sessions,
        private EventDispatcherInterface $events,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return int|null the sessions it ended, null when the person was disabled already
     */
    public function disable(User $user, RequestContext $context): ?int
    {
        return $this->disableOnce($user, $context, null);
    }

    /**
     * The act and the end of each session name the administrator (decision 0038).
     *
     * @return int|null the sessions it ended, null when the person was disabled already
     */
    public function disableByAnAdministrator(User $user, Subject $administrator, RequestContext $context): ?int
    {
        return $this->disableOnce($user, $context, $administrator);
    }

    private function disableOnce(User $user, RequestContext $context, ?Subject $administrator): ?int
    {
        if (! $user->isEnabled()) {
            return null;
        }
        $now = $this->clock->now();
        $user->disable($now);
        $this->users->save($user);
        $ended = $administrator === null
            ? $this->sessions->endEvery($user, $context)
            : $this->sessions->endEveryByAnAdministrator($user, $administrator, $context);
        $this->events->dispatch(new PersonDisabled($user->tenant, $user->subject, $now, $context, $administrator));

        return $ended;
    }
}
