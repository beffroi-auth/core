<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Group\GroupMembership;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\GroupsOfAPersonInterface;
use function usort;

/**
 * For tests and for the examples: nothing here survives the process.
 */
final class InMemoryGroupMembershipRepository implements GroupsOfAPersonInterface
{
    /**
     * @var array<string, GroupMembership>
     */
    private array $memberships = [];

    public function save(GroupMembership $membership): void
    {
        $this->memberships[$membership->id->toRfc4122()] = $membership;
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
