<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Archive\Chapter;

use Beffroi\Core\Domain\Archive\ArchivedFacts;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\ArchiveChapterInterface;
use Beffroi\Core\Port\AuthenticationSessionRepositoryInterface;
use Psr\Clock\ClockInterface;

/**
 * Where a person is signed in, in the archive they ask for (issue #453): one line per session open in
 * their name, what it proved, when it was opened and what the last request on it carried.
 *
 * The sessions open and not every session there ever was: a session that has ended is a row this provider
 * keeps for as long as the protocol needs it and never a place a person is signed in, and what the acts of
 * their history were is the journal, which is its own chapter.
 */
final readonly class SessionsChapter implements ArchiveChapterInterface
{
    public function __construct(
        private AuthenticationSessionRepositoryInterface $sessions,
        private ClockInterface $clock,
    ) {
    }

    public function name(): string
    {
        return 'sessions';
    }

    public function of(User $user): array
    {
        $lines = [];
        foreach ($this->sessions->findActiveByUser($user, $this->clock->now()) as $session) {
            $lines[] = [
                'id' => $session->id->toRfc4122(),
                'acr' => $session->authenticationContextClass()->value,
                'amr' => $session->methodReferences(),
                'established_at' => ArchivedFacts::moment($session->establishedAt),
                'expires_at' => ArchivedFacts::moment($session->expiresAt),
                'last_seen_at' => ArchivedFacts::moment($session->lastSeenAt),
                'opened_from' => ArchivedFacts::context($session->createdFrom),
                'last_seen_from' => ArchivedFacts::context($session->lastSeenFrom),
            ];
        }

        return $lines;
    }
}
