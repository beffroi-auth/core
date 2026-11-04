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
 * Takes the name as it is said, never trimmed or folded: the mapping of a provider names it exactly.
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
     * @throws InvalidArgumentException
     * @throws GroupAlreadyExistsException
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
