<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Group;

use DateTimeImmutable;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final readonly class GroupMembership
{
    public function __construct(
        public Uuid $id,
        public Group $group,
        public User $user,
        public GroupSource $source,
        public DateTimeImmutable $addedAt,
    ) {
        if (! $user->belongsTo($group->tenant)) {
            throw new InvalidArgumentException('A user can only belong to a group of their own tenant.');
        }
    }
}
