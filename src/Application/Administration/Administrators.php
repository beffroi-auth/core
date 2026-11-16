<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Administration;

use function array_any;
use Beffroi\Core\Domain\Group\GroupMembership;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\AdministratorGroupsInterface;
use Beffroi\Core\Port\GroupsOfAPersonInterface;
use function in_array;

/**
 * Who administers a tenant (decision 0038): a person of that tenant who is a member of one of the groups its deployment
 * names, whatever source holds the membership, the tenant itself or a directory (decision 0002). It is read at the
 * moment it is asked, so that a person taken out of the group stops being one at once.
 */
final readonly class Administrators
{
    public function __construct(
        private AdministratorGroupsInterface $groups,
        private GroupsOfAPersonInterface $memberships,
    ) {
    }

    public function include(User $user): bool
    {
        $administering = $this->groups->groupsAdministering($user->tenant);
        if ($administering === []) {
            return false;
        }

        return array_any(
            $this->memberships->of($user),
            static fn (GroupMembership $membership): bool => in_array($membership->group->name, $administering, true),
        );
    }
}
