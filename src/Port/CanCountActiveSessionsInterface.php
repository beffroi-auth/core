<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * How many sessions are open in a tenant at a moment, neither ended nor expired: the statistics of an instance read
 * the number and nothing of whose they are (decision 0038).
 */
interface CanCountActiveSessionsInterface
{
    public function countActiveSessionsIn(Tenant $tenant, DateTimeImmutable $now): int;
}
