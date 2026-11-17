<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Group\Group;
use Beffroi\Core\Domain\Group\GroupMembership;

interface MembersOfAGroupInterface
{
    /**
     * @return list<GroupMembership> in the order they were added
     */
    public function membersOf(Group $group): array;

    public function countMembersOf(Group $group): int;
}
