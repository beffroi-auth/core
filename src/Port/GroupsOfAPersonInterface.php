<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Group\GroupMembership;
use Beffroi\Core\Domain\Identity\User;

/**
 * The groups a person belongs to and where each membership came from (decision 0002), for the archive they
 * ask for (issue #453).
 */
interface GroupsOfAPersonInterface
{
    /**
     * @return list<GroupMembership> by the name of the group
     */
    public function of(User $user): array;
}
