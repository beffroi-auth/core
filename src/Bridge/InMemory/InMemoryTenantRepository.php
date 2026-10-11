<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\TenantRepositoryInterface;

final class InMemoryTenantRepository implements TenantRepositoryInterface
{
    /**
     * @var array<string, Tenant> by tenant id
     */
    public private(set) array $tenants = [];

    public function save(Tenant $tenant): void
    {
        $this->tenants[$tenant->id->toRfc4122()] = $tenant;
    }
}
