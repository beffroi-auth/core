<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Archive\Chapter;

use Beffroi\Core\Domain\Archive\ArchivedFacts;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\ArchiveChapterInterface;
use Beffroi\Core\Port\ChangesOfAPersonInterface;
use function count;

/**
 * A change an administrator of the tenant made says so, and neither who they are nor where they made it from (decision
 * 0038), as an act of theirs does.
 */
final readonly class ChangesChapter implements ArchiveChapterInterface
{
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
                    'from' => $change->author === null ? ArchivedFacts::context($change->context) : null,
                    'author' => $change->author === null ? null : ArchivedFacts::AN_ADMINISTRATOR,
                ];
                $after = $change->id;
            }
        } while (count($page) === self::READ_AT_ONCE);

        return $lines;
    }
}
