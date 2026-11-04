<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * How many people a tenant has, whatever their state: the statistics of an instance read the number and nothing of
 * anybody (decision 0038).
 */
interface CanCountUsersInterface
{
    public function countUsersIn(Tenant $tenant): int;
}
