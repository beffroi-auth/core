<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Domain\Event\PersonEnabled;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Port\UserRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * A person who may sign in again. Nothing they held is given back, every session of theirs having ended with the
 * disabling. A person enabled already is left as they are, and nothing is announced.
 */
final readonly class PersonEnabling
{
    public function __construct(
        private UserRepositoryInterface $users,
        private EventDispatcherInterface $events,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return bool whether the person was disabled until now
     */
    public function enable(User $user, RequestContext $context): bool
    {
        return $this->enableOnce($user, $context, null);
    }

    /**
     * The act names the administrator (decision 0038).
     *
     * @return bool whether the person was disabled until now
     */
    public function enableByAnAdministrator(User $user, Subject $administrator, RequestContext $context): bool
    {
        return $this->enableOnce($user, $context, $administrator);
    }

    private function enableOnce(User $user, RequestContext $context, ?Subject $administrator): bool
    {
        if ($user->isEnabled()) {
            return false;
        }
        $now = $this->clock->now();
        $user->enable($now);
        $this->users->save($user);
        $this->events->dispatch(new PersonEnabled($user->tenant, $user->subject, $now, $context, $administrator));

        return true;
    }
}
