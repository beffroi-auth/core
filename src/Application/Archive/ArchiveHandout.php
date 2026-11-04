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
