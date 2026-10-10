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
 * The archive of a person dropped because they asked for it to be (issue #453): the file they have is a
 * file this provider no longer holds, before the date it would have gone away on by itself.
 *
 * **The row goes and does not stay revoked**, unlike everything else a person takes out of their account.
 * A subscription or a credential is kept because a row saying a browser was reachable is worth something;
 * an archive is the one file where every sealed column stands in clear at once, so the only state worth
 * keeping is none, and the act is written to the journal, where it says what became of the request.
 *
 * **There is no identifier to pass**, as in ArchiveHandout: what this drops is the last archive of the
 * person who asks, so the archive of somebody else is not refused here, it is not reachable from here.
 *
 * **A file still being built is not dropped.** There is nothing to drop yet, and the build is on its way
 * to a row it would then not find; asking for one again once it is ready is what replaces it.
 */
final readonly class ArchiveDisposal
{
    public function __construct(
        private PersonalArchiveRepositoryInterface $archives,
        private EventDispatcherInterface $events,
        private ClockInterface $clock,
    ) {
    }

    /**
     * Says whether there was a file to drop, so that the page of the account can tell the person who
     * clicked twice from the person whose file went away under them.
     */
    public function droppedBy(User $user, RequestContext $context): bool
    {
        $archive = $this->archives->latestOf($user);
        if ($archive === null || ! $archive->isReady()) {
            return false;
        }
        $this->archives->forget($archive);
        $this->events->dispatch(new PersonDroppedTheirArchive($this->clock->now(), $user->tenant, $archive->id, $user->subject, $context));

        return true;
    }
}
