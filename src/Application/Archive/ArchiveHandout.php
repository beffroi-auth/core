<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Archive;

use Beffroi\Core\Domain\Archive\PersonalArchive;
use Beffroi\Core\Domain\Event\PersonTookTheirArchive;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Port\PersonalArchiveRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * The archive handed to the person who asked for it (issue #453), and to nobody else.
 *
 * **There is no identifier to pass.** What this gives is the last archive of the person who asks, so
 * nothing is guessed and nothing compared: the refusal that matters most in this issue is one no caller
 * can forget to write. An archive of somebody else is not refused here, it is not reachable from here.
 *
 * Nothing to give is null and not a refusal: a person who never asked, one whose archive is still being
 * built and one whose archive has expired are three states of the same page, which says which of them it
 * is (issue #453 asks that an archive still being built say so rather than give nothing).
 */
final readonly class ArchiveHandout
{
    public function __construct(
        private PersonalArchiveRepositoryInterface $archives,
        private EventDispatcherInterface $events,
        private ClockInterface $clock,
    ) {
    }

    public function givenTo(User $user, RequestContext $context): ?PersonalArchive
    {
        $now = $this->clock->now();
        $archive = $this->archives->latestOf($user);
        if ($archive === null || ! $archive->isReady() || $archive->hasExpired($now)) {
            return null;
        }
        $this->events->dispatch(new PersonTookTheirArchive($now, $user->tenant, $archive->id, $user->subject, $context));

        return $archive;
    }
}
