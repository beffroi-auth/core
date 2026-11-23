<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Group\Group;
use Beffroi\Core\Domain\Group\GroupMembership;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;

interface MembersOfAGroupInterface
{
    /**
     * @return Listing<GroupMembership> in the order they were added, a page past the last one being the last one
     */
    public function membersOf(Group $group, Pagination $pagination): Listing;

    public function countMembersOf(Group $group): int;
}
