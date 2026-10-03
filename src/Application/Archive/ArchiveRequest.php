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
 * A person asks for everything this provider holds about them (RGPD article 15, issue #453).
 *
 * **It builds nothing.** It writes the row, announces the act, and the archive is assembled off the
 * request by whatever carries the message: a person reading nine tables and signing one file is a person
 * waiting on a page, and a page that waits is a page a timeout gives nothing.
 *
 * **Asking again while one is being built gives that one back**, so a second click is not a second build
 * and nobody fills a queue by holding a button. Asking once one is ready starts a new one: what a person
 * asks for is what this provider holds now, and a file built last Tuesday is not that.
 */
final readonly class ArchiveRequest
{
    public function __construct(
        private PersonalArchiveRepositoryInterface $archives,
        private EventDispatcherInterface $events,
        private ClockInterface $clock,
    ) {
    }

    public function askedBy(User $user, RequestContext $context): PersonalArchive
    {
        $now = $this->clock->now();
        $pending = $this->archives->latestOf($user);
        if ($pending !== null && ! $pending->isReady() && ! $pending->hasExpired($now)) {
            return $pending;
        }
        $archive = PersonalArchive::asked(Uuid::v7(), $user, $now);
        $this->archives->save($archive);
        $this->events->dispatch(new PersonAskedForTheirArchive($now, $user->tenant, $archive->id, $user->subject, $context));

        return $archive;
    }
}
