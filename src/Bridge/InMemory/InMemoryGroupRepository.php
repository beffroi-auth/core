<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Group\Group;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\GroupAlreadyExistsException;
use Beffroi\Core\Port\GroupRepositoryInterface;

/**
 * For tests and for the examples: nothing here survives the process.
 */
final class InMemoryGroupRepository implements GroupRepositoryInterface
{
    /**
     * @var array<string, Group>
     */
    private array $groups = [];

    public function findByName(Tenant $tenant, string $name): ?Group
    {
        foreach ($this->groups as $group) {
            if ($group->tenant->id->equals($tenant->id) && $group->name === $name) {
                return $group;
            }
        }

        return null;
    }

    public function save(Group $group): void
    {
        $holder = $this->findByName($group->tenant, $group->name);
        if ($holder !== null && ! $holder->id->equals($group->id)) {
            throw GroupAlreadyExistsException::named($group->name);
        }
        $this->groups[$group->id->toRfc4122()] = $group;
    }
}
