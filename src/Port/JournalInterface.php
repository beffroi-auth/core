<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Journal\JournalEntry;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

interface JournalInterface
{
    public function record(JournalEntry $entry): void;

    /**
     * Bounded by the implementation: a backlog is worked through over several runs.
     */
    public function forgetBefore(Tenant $tenant, DateTimeImmutable $moment): int;
}
