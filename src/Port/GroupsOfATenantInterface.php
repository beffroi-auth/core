<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Group\Group;
use Beffroi\Core\Domain\Tenant\Tenant;
use Symfony\Component\Uid\Uuid;

interface GroupsOfATenantInterface
{
    /**
     * @return list<Group> by name
     */
    public function of(Tenant $tenant): array;

    /**
     * @return Group|null null when the tenant has no group of that identifier, another tenant's included
     */
    public function find(Tenant $tenant, Uuid $id): ?Group;
}
