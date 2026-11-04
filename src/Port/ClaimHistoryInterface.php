<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\ClaimChange;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * Not a message: a change of a record and its history commit together, in one unit of work.
 */
interface ClaimHistoryInterface
{
    public function record(ClaimChange $change): void;

    /**
     * Bounded by the implementation: a backlog is worked through over several runs.
     */
    public function forgetBefore(Tenant $tenant, DateTimeImmutable $moment): int;
}
