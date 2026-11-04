<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;

interface TenantRepositoryInterface
{
    public function save(Tenant $tenant): void;
}
