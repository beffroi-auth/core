<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Journal\JournalEntry;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * Where the acts of this provider are written down and forgotten again (issue #454). Both are writes,
 * which is why they are one port; reading them is another (ActsOfAPersonInterface).
 */
interface JournalInterface
{
    public function record(JournalEntry $entry): void;

    /**
     * Forgets the acts of this tenant older than the moment given, and says how many it forgot. Bounded by
     * the implementation, so a backlog is worked through over several runs rather than in one statement
     * that holds a table.
     */
    public function forgetBefore(Tenant $tenant, DateTimeImmutable $moment): int;
}
