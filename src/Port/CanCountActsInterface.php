<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * How many acts of each name the journal of a tenant holds since a moment, the most frequent first: the statistics
 * of an instance read the numbers and nothing of whose acts they are (decision 0038), over what the journal keeps
 * and never beyond it.
 */
interface CanCountActsInterface
{
    /**
     * @return array<string, int> the number of acts of each name, by the name the events give themselves
     */
    public function countActsSince(Tenant $tenant, DateTimeImmutable $since): array;
}
