<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Journal\JournalEntry;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;

/**
 * A companion of ActsOfAPersonInterface: one page of what the journal holds of one person, for the table of
 * their account that shows it (architecture §5.2).
 *
 * A person reads the pages of their own journal back and forth, which is a page and a count, where the
 * archive walks the whole of one once and reads past the last act it read (everyActOf). Which acts answer
 * the question stays the caller's, as it is there.
 */
interface CanPageActsOfAPersonInterface
{
    /**
     * @param list<string> $acts the names of the acts to read, as the events give them
     *
     * @return Listing<JournalEntry> the most recent first
     */
    public function pageOfActs(User $user, array $acts, Pagination $pagination): Listing;
}
