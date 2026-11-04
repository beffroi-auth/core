<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Group\GroupMembership;
use Beffroi\Core\Domain\Identity\User;

interface GroupsOfAPersonInterface
{
    /**
     * @return list<GroupMembership> by the name of the group
     */
    public function of(User $user): array;
}
