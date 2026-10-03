<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Journal\JournalEntry;

/**
 * What the journal holds of one person (issue #454), for the page of their account that shows it.
 *
 * The acts are named by the caller, because which acts answer a question is the question's: the page of
 * the recent sign-ins asks for the two that mean somebody tried to sign in, and the archive of a person
 * will ask for all of theirs.
 */
interface ActsOfAPersonInterface
{
    /**
     * @param list<string> $acts the names of the acts to read, as the events give them
     *
     * @return list<JournalEntry> the most recent first
     */
    public function lastOf(User $user, array $acts, int $howMany): array;
}
