<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Journal\JournalEntry;
use Symfony\Component\Uid\Uuid;

/**
 * What the journal holds of one person (issue #454), for the page of their account that shows it.
 *
 * The acts are named by the caller, because which acts answer a question is the question's: the home of
 * the account asks for the two that mean somebody tried to sign in (issue #842), where the archive of a
 * person asks for all of theirs and names none (issue #453).
 */
interface ActsOfAPersonInterface
{
    /**
     * @param list<string> $acts the names of the acts to read, as the events give them
     *
     * @return list<JournalEntry> the most recent first
     */
    public function lastOf(User $user, array $acts, int $howMany): array;

    /**
     * Every act of that person, whatever its name, oldest first, in pages: the reading the archive of a
     * person needs (issue #453), where the page of their account reads the last few of a few names.
     *
     * It is paged rather than bounded, because the answer is the whole journal of that person and a
     * deployment that keeps a year of it is not a query that holds the table. The identifier of an act is
     * a UUIDv7, which sorts chronologically, so a page is a lookup past the last act read.
     *
     * @param Uuid|null $after the last act the caller read, null for the first page
     *
     * @return list<JournalEntry> the oldest first
     */
    public function everyActOf(User $user, int $howMany, ?Uuid $after = null): array;
}
