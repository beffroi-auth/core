<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use function array_filter;
use function array_values;
use function count;
use Beffroi\Core\Domain\Group\Group;
use Beffroi\Core\Domain\Group\GroupMembership;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;
use Beffroi\Core\Port\GroupMembershipRepositoryInterface;
use Beffroi\Core\Port\GroupsOfAPersonInterface;
use Beffroi\Core\Port\MembersOfAGroupInterface;
use function usort;

final class InMemoryGroupMembershipRepository implements GroupMembershipRepositoryInterface, GroupsOfAPersonInterface, MembersOfAGroupInterface
{
    /**
     * @var array<string, GroupMembership>
     */
    private array $memberships = [];

    public function save(GroupMembership $membership): void
    {
        $this->memberships[$membership->id->toRfc4122()] = $membership;
    }

    public function remove(GroupMembership $membership): void
    {
        unset($this->memberships[$membership->id->toRfc4122()]);
    }

    public function membersOf(Group $group, Pagination $pagination): Listing
    {
        return Listing::of($this->allMembersOf($group), $pagination);
    }

    public function countMembersOf(Group $group): int
    {
        return count($this->allMembersOf($group));
    }

    /**
     * @return list<GroupMembership> in the order they were added
     */
    private function allMembersOf(Group $group): array
    {
        $members = array_values(array_filter(
            $this->memberships,
            static fn (GroupMembership $membership): bool => $membership->group->id->equals($group->id),
        ));
        usort($members, static fn (GroupMembership $a, GroupMembership $b): int => $a->addedAt <=> $b->addedAt);

        return $members;
    }

    public function of(User $user): array
    {
        $theirs = [];
        foreach ($this->memberships as $membership) {
            if ($membership->user->id->equals($user->id)) {
                $theirs[] = $membership;
            }
        }
        usort($theirs, static fn (GroupMembership $a, GroupMembership $b): int => $a->group->name <=> $b->group->name);

        return $theirs;
    }
}
