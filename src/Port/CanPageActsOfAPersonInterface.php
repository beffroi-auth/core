<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Journal\JournalEntry;
use Beffroi\Core\Domain\Journal\JournalQuestion;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;

/**
 * A companion of ActsOfAPersonInterface: one page of what the journal holds of one person, for the table of
 * their account that shows it (architecture §5.2).
 *
 * A person reads the pages of their own journal back and forth, which is a page and a count, where the
 * archive walks the whole of one once and reads past the last act it read (everyActOf). Which acts answer
 * the question stays the caller's, as it is there: what it asks travels whole (JournalQuestion), so a
 * reading that asks of one application or of none asks the same store the same way.
 */
interface CanPageActsOfAPersonInterface
{
    /**
     * @return Listing<JournalEntry> the most recent first
     */
    public function pageOfActs(User $user, JournalQuestion $question, Pagination $pagination): Listing;
}
