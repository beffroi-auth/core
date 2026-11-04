<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;
use RuntimeException;

/**
 * Never a default tenant: a value sealed outside the scope of its tenant escapes the destruction of that
 * tenant's keys.
 */
interface AmbientTenantInterface
{
    /**
     * @throws RuntimeException when no tenant is ambient, or it does not match the deployment's configuration
     */
    public function provide(): Tenant;
}
