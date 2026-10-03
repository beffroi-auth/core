<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Archive\Chapter;

use Beffroi\Core\Domain\Archive\ArchivedFacts;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\ArchiveChapterInterface;
use Beffroi\Core\Port\GroupsOfAPersonInterface;

/**
 * The groups a person belongs to, in the archive they ask for (issue #453): the name of each, where the
 * membership came from and when it was added.
 *
 * The source is on the line because it is the answer to what a person does about it (decision 0002): a
 * membership this provider was told of by a directory is not one this provider decided, and a person
 * reading their file learns which door to knock on.
 */
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
