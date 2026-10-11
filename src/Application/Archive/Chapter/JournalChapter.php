<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Archive\Chapter;

use Beffroi\Core\Domain\Archive\ArchivedFacts;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\ActsOfAPersonInterface;
use Beffroi\Core\Port\ArchiveChapterInterface;
use function count;

/**
 * The journal of a person, in the archive they ask for (issue #453): every act this provider wrote down
 * about them, oldest first, with what the request carried of where it came from.
 *
 * **Every act and not the sign-ins alone.** The page of their account shows them the last twenty attempts
 * because that is what a person reads at a glance (SignInsPage); the file is where the rest is, which is
 * what makes it worth asking for.
 *
 * **It is read in pages.** A person with years of history is not a query that holds the table: the
 * identifier of an act is a UUIDv7, which sorts chronologically, so each page is a lookup past the last
 * act read and never a sort of the whole journal.
 */
final readonly class JournalChapter implements ArchiveChapterInterface
{
    /**
     * How many acts one page reads. A bound and not a tuning: what fixes the size of the file is how long
     * the journal keeps an act, and what this fixes is how much of it is in memory at once.
     */
    public const int READ_AT_ONCE = 500;

    public function __construct(
        private ActsOfAPersonInterface $acts,
    ) {
    }

    public function name(): string
    {
        return 'journal';
    }

    public function of(User $user): array
    {
        $lines = [];
        $after = null;
        do {
            $page = $this->acts->everyActOf($user, self::READ_AT_ONCE, $after);
            foreach ($page as $act) {
                $lines[] = ArchivedFacts::act($act);
                $after = $act->id;
            }
        } while (count($page) === self::READ_AT_ONCE);

        return $lines;
    }
}
