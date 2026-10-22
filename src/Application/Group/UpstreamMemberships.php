<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Group;

use Beffroi\Core\Domain\Group\GroupMapping;
use Beffroi\Core\Domain\Group\GroupMembership;
use Beffroi\Core\Domain\Group\GroupSource;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\GroupMembershipRepositoryInterface;
use Beffroi\Core\Port\GroupsOfAPersonInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use Psr\Clock\ClockInterface;

/**
 * The groups an upstream provider asserts at a sign-in, mapped to the groups of the tenant by the declaration
 * of that provider (decision 0002, issue #782).
 *
 * The memberships of source `upstream_claims` are what the provider said at the last sign-in, so they are
 * replaced by what it says at this one: a membership whose group is no longer asserted goes, one whose group
 * still is stays as it was, with its date, and a group newly asserted gets one. A value the mapping does not
 * name is ignored, so nothing is created from what a provider sends.
 *
 * A membership of another source, `local` or `scim`, is left as it is: a group asserted that the person
 * already holds through one of them gives no second membership, the table holding one per group and person,
 * and the value gone does not take it away.
 *
 * The mapping is an argument, which the caller holds from the declaration of the provider: nothing here reads
 * a configuration.
 *
 * **It runs inside one atomic unit with the sign-in that calls it**, the provisioning of the person included
 * (AtomicWritesInterface::atomically()): the store writes a row at a time, and the unit is what makes the
 * removals and the additions one write, or none. **The write is synchronous**, because a membership taken
 * away must be written before the session that sign-in opens serves anything: queued, it would leave the
 * person in a group the provider no longer asserts for as long as the queue takes. The removals come before
 * the additions. No event.
 */
final readonly class UpstreamMemberships
{
    public function __construct(
        private GroupsOfAPersonInterface $groups,
        private GroupMembershipRepositoryInterface $memberships,
        private UuidGeneratorInterface $uuids,
        private ClockInterface $clock,
    ) {
    }

    /**
     * Nothing is written when a membership cannot be made, so a mapping wired with a group of another tenant
     * leaves the person as they were.
     *
     * @param list<mixed> $asserted the values of the attribute the declaration names for groups, as the provider sent them; what is not a string is ignored
     *
     * @throws InvalidArgumentException when the mapping leads to a group of another tenant than the person's
     */
    public function replace(User $user, array $asserted, GroupMapping $mapping): void
    {
        $mapped = [];
        foreach ($mapping->groupsOf($asserted) as $group) {
            $mapped[$group->id->toRfc4122()] = $group;
        }

        $held = [];
        $left = [];
        foreach ($this->groups->of($user) as $membership) {
            $group = $membership->group->id->toRfc4122();
            if ($membership->source === GroupSource::UpstreamClaims && ! isset($mapped[$group])) {
                $left[] = $membership;

                continue;
            }
            $held[$group] = true;
        }

        $now = $this->clock->now();
        $joined = [];
        foreach ($mapped as $group => $mappedGroup) {
            if (! isset($held[$group])) {
                $joined[] = new GroupMembership($this->uuids->timeOrdered(), $mappedGroup, $user, GroupSource::UpstreamClaims, $now);
            }
        }

        foreach ($left as $membership) {
            $this->memberships->remove($membership);
        }
        foreach ($joined as $membership) {
            $this->memberships->save($membership);
        }
    }
}
