<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Archive;

use Beffroi\Core\Domain\Event\PersonDroppedTheirArchive;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Port\PersonalArchiveRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Forgets the row rather than revoking it: an archive holds every sealed column in clear.
 */
final readonly class ArchiveDisposal
{
    public function __construct(
        private PersonalArchiveRepositoryInterface $archives,
        private EventDispatcherInterface $events,
        private ClockInterface $clock,
    ) {
    }

    public function droppedBy(User $user, RequestContext $context): bool
    {
        $archive = $this->archives->latestOf($user);
        if ($archive === null) {
            return false;
        }
        $this->archives->forget($archive);
        $this->events->dispatch(new PersonDroppedTheirArchive($this->clock->now(), $user->tenant, $archive->id, $user->subject, $context));

        return true;
    }
}
