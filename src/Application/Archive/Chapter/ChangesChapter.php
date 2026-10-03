<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Archive\Chapter;

use Beffroi\Core\Domain\Archive\ArchivedFacts;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\ArchiveChapterInterface;
use Beffroi\Core\Port\ChangesOfAPersonInterface;
use function count;

/**
 * What this person changed of their own record, in the archive they ask for (issues #453 and #482): one
 * line per change, with what the claim said and what they made it say.
 *
 * It is in the file because it is what this provider holds of them: the record says what they are called
 * now, and this says what it held before, which is a personal datum of theirs as much as the other.
 *
 * It is read in pages, as the journal is, the identifier of a change being a UUIDv7.
 */
final readonly class ChangesChapter implements ArchiveChapterInterface
{
    /**
     * How many changes one page reads, the bound of the journal's chapter and for the same reason.
     */
    public const int READ_AT_ONCE = 500;

    public function __construct(
        private ChangesOfAPersonInterface $changes,
    ) {
    }

    public function name(): string
    {
        return 'changes';
    }

    public function of(User $user): array
    {
        $lines = [];
        $after = null;
        do {
            $page = $this->changes->everyChangeOf($user, self::READ_AT_ONCE, $after);
            foreach ($page as $change) {
                $lines[] = [
                    'claim' => $change->claim,
                    'was' => $change->was,
                    'became' => $change->became,
                    'changed_at' => ArchivedFacts::moment($change->changedAt),
                    'from' => ArchivedFacts::context($change->context),
                ];
                $after = $change->id;
            }
        } while (count($page) === self::READ_AT_ONCE);

        return $lines;
    }
}
