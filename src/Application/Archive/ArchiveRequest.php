<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Archive;

use Beffroi\Core\Domain\Archive\PersonalArchive;
use Beffroi\Core\Domain\Event\PersonAskedForTheirArchive;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Port\PersonalArchiveRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * RGPD article 15
 */
final readonly class ArchiveRequest
{
    public function __construct(
        private PersonalArchiveRepositoryInterface $archives,
        private EventDispatcherInterface $events,
        private ClockInterface $clock,
    ) {
    }

    /**
     * A new archive replaces the one before, ready or not, because each one holds every sealed column in clear.
     */
    public function askedBy(User $user, RequestContext $context): PersonalArchive
    {
        $now = $this->clock->now();
        $previous = $this->archives->latestOf($user);
        if ($previous !== null && ! $previous->isReady() && ! $previous->hasExpired($now) && ! $previous->hasStalled($now)) {
            return $previous;
        }
        if ($previous !== null) {
            $this->archives->forget($previous);
        }
        $archive = PersonalArchive::asked(Uuid::v7(), $user, $now);
        $this->archives->save($archive);
        $this->events->dispatch(new PersonAskedForTheirArchive($now, $user->tenant, $archive->id, $user->subject, $context));

        return $archive;
    }
}
