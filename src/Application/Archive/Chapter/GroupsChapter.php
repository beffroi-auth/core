<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Archive\Chapter;

use Beffroi\Core\Domain\Archive\ArchivedFacts;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\ArchiveChapterInterface;
use Beffroi\Core\Port\GroupsOfAPersonInterface;

final readonly class GroupsChapter implements ArchiveChapterInterface
{
    public function __construct(
        private GroupsOfAPersonInterface $groups,
    ) {
    }

    public function name(): string
    {
        return 'groups';
    }

    public function of(User $user): array
    {
        $lines = [];
        foreach ($this->groups->of($user) as $membership) {
            $lines[] = [
                'name' => $membership->group->name,
                'source' => $membership->source->value,
                'added_at' => ArchivedFacts::moment($membership->addedAt),
            ];
        }

        return $lines;
    }
}
