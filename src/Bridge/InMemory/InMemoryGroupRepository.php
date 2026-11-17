<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Group\Group;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\GroupAlreadyExistsException;
use Beffroi\Core\Port\GroupRepositoryInterface;
use Beffroi\Core\Port\GroupsOfATenantInterface;
use Symfony\Component\Uid\Uuid;
use function array_filter;
use function array_values;
use function usort;

final class InMemoryGroupRepository implements GroupRepositoryInterface, GroupsOfATenantInterface
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

    public function of(Tenant $tenant): array
    {
        $theirs = array_values(array_filter($this->groups, static fn (Group $group): bool => $group->tenant->id->equals($tenant->id)));
        usort($theirs, static fn (Group $a, Group $b): int => $a->name <=> $b->name);

        return $theirs;
    }

    public function find(Tenant $tenant, Uuid $id): ?Group
    {
        $group = $this->groups[$id->toRfc4122()] ?? null;

        return $group !== null && $group->tenant->id->equals($tenant->id) ? $group : null;
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
