<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

interface DeadRecordsInterface
{
    /**
     * @param int $limit the most rows one statement may drop, one statement per table
     */
    public function forgetWhatIsDead(Tenant $tenant, DateTimeImmutable $now, int $limit): int;
}
