<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Journal\JournalEntry;
use Beffroi\Core\Domain\Tenant\Tenant;

interface JournalInterface
{
    /**
     * The act takes the next position of the chain of its tenant.
     */
    public function record(JournalEntry $entry): void;

    /**
     * Forgets the acts the chain of the tenant holds up to that position, and their links.
     */
    public function forgetUpTo(Tenant $tenant, int $position): int;
}
