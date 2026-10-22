<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Group;

use Beffroi\Core\Domain\Group\Group;
use Beffroi\Core\Domain\Group\GroupSource;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\GroupAlreadyExistsException;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\GroupRepositoryInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use Psr\Clock\ClockInterface;

/**
 * Creates the groups of a tenant, from a console, a fixture or an import, as UserProvisioner creates its
 * persons (decision 0002). A group is local: it is what the deployment decided to hold, and what the
 * declaration of an upstream provider maps the values it asserts to (issue #782), never what a provider
 * sent. Synchronous writes, no event.
 *
 * The name is taken as it is said, never trimmed or folded: the mapping of a provider names it exactly, so
 * what the group refuses (Group::rename()) is refused here rather than quietly made into another name.
 */
final readonly class GroupProvisioner
{
    public function __construct(
        private GroupRepositoryInterface $groups,
        private UuidGeneratorInterface $uuids,
        private ClockInterface $clock,
    ) {
    }

    /**
     * The lookup gives the answer a person reads; the store gives the same one to a creation that raced this
     * one between the lookup and the write.
     *
     * @throws InvalidArgumentException when the name is empty, longer than its column, not in NFC, or begins or ends with whitespace
     * @throws GroupAlreadyExistsException when the tenant already has a group of that name
     */
    public function create(Tenant $tenant, string $name): Group
    {
        if ($this->groups->findByName($tenant, $name) !== null) {
            throw GroupAlreadyExistsException::named($name);
        }
        $group = new Group($this->uuids->timeOrdered(), $tenant, $name, GroupSource::Local, $this->clock->now());
        $this->groups->save($group);

        return $group;
    }
}
