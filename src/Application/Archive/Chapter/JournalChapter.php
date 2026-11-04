<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Archive\Chapter;

use Beffroi\Core\Domain\Archive\ArchivedFacts;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\ActsOfAPersonInterface;
use Beffroi\Core\Port\ArchiveChapterInterface;
use function count;

final readonly class JournalChapter implements ArchiveChapterInterface
{
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
