<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Group;

use function array_find;
use Beffroi\Core\Domain\Group\Group;
use Beffroi\Core\Domain\Group\GroupMembership;
use Beffroi\Core\Domain\Group\GroupSource;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Exception\GroupMembershipException;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\GroupMembershipRepositoryInterface;
use Beffroi\Core\Port\GroupsOfAPersonInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use Psr\Clock\ClockInterface;

/**
 * The members the tenant holds itself in its groups (decision 0002), from a console or a fixture, as GroupProvisioner
 * creates the groups. What a directory holds is written by that directory alone: a group SCIM fills takes no member
 * from here, and a membership an upstream provider or SCIM gave is not taken back from here, the next assertion or the
 * next push writing it again. Synchronous writes, no event.
 */
final readonly class LocalMemberships
{
    public function __construct(
        private GroupMembershipRepositoryInterface $memberships,
        private GroupsOfAPersonInterface $groupsOfAPerson,
        private UuidGeneratorInterface $uuids,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws GroupMembershipException when a directory fills the group, or the person already is one of its members
     * @throws InvalidArgumentException when the person belongs to another tenant than the group
     */
    public function add(Group $group, User $user): GroupMembership
    {
        if ($group->source->isManaged()) {
            throw GroupMembershipException::filledByADirectory($group->name);
        }
        if ($this->membershipOf($group, $user) !== null) {
            throw GroupMembershipException::alreadyAMember($group->name);
        }
        $membership = new GroupMembership($this->uuids->timeOrdered(), $group, $user, GroupSource::Local, $this->clock->now());
        $this->memberships->save($membership);

        return $membership;
    }

    /**
     * @throws GroupMembershipException when the person is not a member of the group, or is one by a directory
     */
    public function remove(Group $group, User $user): void
    {
        $membership = $this->membershipOf($group, $user) ?? throw GroupMembershipException::notAMember($group->name);
        if ($membership->source->isManaged()) {
            throw GroupMembershipException::givenByADirectory($group->name);
        }
        $this->memberships->remove($membership);
    }

    private function membershipOf(Group $group, User $user): ?GroupMembership
    {
        return array_find(
            $this->groupsOfAPerson->of($user),
            static fn (GroupMembership $membership): bool => $membership->group->id->equals($group->id),
        );
    }
}
