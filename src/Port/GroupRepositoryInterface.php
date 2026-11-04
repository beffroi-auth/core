<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Group\Group;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\GroupAlreadyExistsException;

interface GroupRepositoryInterface
{
    /**
     * @param string $name compared exactly, as the unique constraint holds it
     */
    public function findByName(Tenant $tenant, string $name): ?Group;

    /**
     * @throws GroupAlreadyExistsException also when a concurrent creation wrote that name after the lookup
     */
    public function save(Group $group): void;
}
