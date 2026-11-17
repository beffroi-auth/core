<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Group;

use function array_find;
use Beffroi\Core\Domain\Event\GroupMembershipAdded;
use Beffroi\Core\Domain\Event\GroupMembershipRemoved;
use Beffroi\Core\Domain\Group\Group;
use Beffroi\Core\Domain\Group\GroupMembership;
use Beffroi\Core\Domain\Group\GroupSource;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Exception\GroupMembershipException;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\GroupMembershipRepositoryInterface;
use Beffroi\Core\Port\GroupsOfAPersonInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * The members the tenant holds itself in its groups (decision 0002), from the console or the administration API, as
 * GroupProvisioner creates the groups. What a directory holds is written by that directory alone: a group SCIM fills
 * takes no member from here, and a membership an upstream provider or SCIM gave is not taken back from here, the next
 * assertion or the next push writing it again. A membership added or taken back is an act of the person, since it can
 * make or unmake an administrator (decision 0038).
 */
final readonly class LocalMemberships
{
    public function __construct(
        private GroupMembershipRepositoryInterface $memberships,
        private GroupsOfAPersonInterface $groupsOfAPerson,
        private UuidGeneratorInterface $uuids,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * @throws GroupMembershipException when a directory fills the group, or the person already is one of its members
     * @throws InvalidArgumentException when the person belongs to another tenant than the group
     */
    public function add(Group $group, User $user, RequestContext $context): GroupMembership
    {
        return $this->addOnce($group, $user, $context, null);
    }

    /**
     * The act names the administrator (decision 0038).
     *
     * @throws GroupMembershipException when a directory fills the group, or the person already is one of its members
     * @throws InvalidArgumentException when the person belongs to another tenant than the group
     */
    public function addByAnAdministrator(Group $group, User $user, Subject $administrator, RequestContext $context): GroupMembership
    {
        return $this->addOnce($group, $user, $context, $administrator);
    }

    /**
     * @throws GroupMembershipException when the person is not a member of the group, or is one by a directory
     */
    public function remove(Group $group, User $user, RequestContext $context): void
    {
        $this->removeOnce($group, $user, $context, null);
    }

    /**
     * The act names the administrator (decision 0038).
     *
     * @throws GroupMembershipException when the person is not a member of the group, or is one by a directory
     */
    public function removeByAnAdministrator(Group $group, User $user, Subject $administrator, RequestContext $context): void
    {
        $this->removeOnce($group, $user, $context, $administrator);
    }

    /**
     * @throws GroupMembershipException
     * @throws InvalidArgumentException
     */
    private function addOnce(Group $group, User $user, RequestContext $context, ?Subject $administrator): GroupMembership
    {
        if ($group->source->isManaged()) {
            throw GroupMembershipException::filledByADirectory($group->name);
        }
        if ($this->membershipOf($group, $user) !== null) {
            throw GroupMembershipException::alreadyAMember($group->name);
        }
        $now = $this->clock->now();
        $membership = new GroupMembership($this->uuids->timeOrdered(), $group, $user, GroupSource::Local, $now);
        $this->memberships->save($membership);
        $this->events->dispatch(new GroupMembershipAdded($group->tenant, $user->subject, $group->name, $now, $context, $administrator));

        return $membership;
    }

    /**
     * @throws GroupMembershipException
     */
    private function removeOnce(Group $group, User $user, RequestContext $context, ?Subject $administrator): void
    {
        $membership = $this->membershipOf($group, $user) ?? throw GroupMembershipException::notAMember($group->name);
        if ($membership->source->isManaged()) {
            throw GroupMembershipException::givenByADirectory($group->name);
        }
        $this->memberships->remove($membership);
        $this->events->dispatch(new GroupMembershipRemoved($group->tenant, $user->subject, $group->name, $this->clock->now(), $context, $administrator));
    }

    private function membershipOf(Group $group, User $user): ?GroupMembership
    {
        return array_find(
            $this->groupsOfAPerson->of($user),
            static fn (GroupMembership $membership): bool => $membership->group->id->equals($group->id),
        );
    }
}
