<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Group\Group;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\GroupAlreadyExistsException;

/**
 * The groups of a tenant (decision 0002), found by the name the tenant knows them by: what the console names
 * when it creates one, and what the declaration of an upstream provider names when it maps the values that
 * provider asserts to the groups of the tenant (issue #782).
 */
interface GroupRepositoryInterface
{
    /**
     * @param string $name compared exactly, as the unique constraint of the table holds it
     *
     * @return Group|null null when the tenant has no group of that name
     */
    public function findByName(Tenant $tenant, string $name): ?Group;

    /**
     * @throws GroupAlreadyExistsException when another group of the tenant holds that name, which a creation
     *                                     running beside this one may have written after the lookup
     */
    public function save(Group $group): void;
}
