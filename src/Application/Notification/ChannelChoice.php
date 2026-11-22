<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Notification;

use function array_keys;
use function array_unique;
use Beffroi\Core\Domain\Event\NotificationChannelsChosen;
use Beffroi\Core\Domain\Identity\ClaimChange;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Notification\ChosenChannels;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Port\AtomicWritesInterface;
use Beffroi\Core\Port\ClaimHistoryInterface;
use Beffroi\Core\Port\UserRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function sort;
use Symfony\Component\Uid\Uuid;

/**
 * A person chooses the channels that tell them, written whole or not at all: each choice that changed is a row of
 * their history, and the choice one act of theirs. A choice that changes nothing writes nothing.
 */
final readonly class ChannelChoice
{
    public function __construct(
        private UserRepositoryInterface $users,
        private ClaimHistoryInterface $history,
        private AtomicWritesInterface $writes,
        private EventDispatcherInterface $events,
        private ClockInterface $clock,
    ) {
    }

    public function choose(User $user, ChosenChannels $chosen, RequestContext $context): void
    {
        $before = $user->notificationChannels->history();
        $after = $chosen->history();
        $changed = [];
        $names = array_unique([...array_keys($before), ...array_keys($after)]);
        sort($names);
        foreach ($names as $name) {
            if (($before[$name] ?? null) !== ($after[$name] ?? null)) {
                $changed[] = $name;
            }
        }
        if ($changed === []) {
            return;
        }
        $now = $this->clock->now();
        $this->writes->atomically(function () use ($user, $chosen, $before, $after, $changed, $now, $context): void {
            $user->chooseChannels($chosen, $now);
            $this->users->save($user);
            foreach ($changed as $name) {
                $this->history->record(new ClaimChange(Uuid::v7(), $user, $name, $before[$name] ?? null, $after[$name] ?? null, $now, $context));
            }
        });
        $this->events->dispatch(new NotificationChannelsChosen($user->tenant, $user->subject, $changed, $now, $context));
    }
}
