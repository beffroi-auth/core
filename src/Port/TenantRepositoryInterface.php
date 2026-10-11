<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * Where what a tenant decided of itself is written (issue #327). The tenant of a request is read through
 * AmbientTenantInterface, which every use case is handed; this is for the acts that change the row, the
 * floor of its logins being the first of them, so that an administrator hardening a tenant is one use case
 * and not a command that knows a store.
 */
interface TenantRepositoryInterface
{
    public function save(Tenant $tenant): void;
}
