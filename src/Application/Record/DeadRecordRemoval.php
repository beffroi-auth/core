<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Record;

use function array_sum;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\DeadRecordsInterface;
use Psr\Clock\ClockInterface;

final readonly class DeadRecordRemoval
{
    /**
     * Rows one statement of a store drops: small enough not to hold a table while a login waits on it.
     */
    public const int PER_RUN = 1000;

    /**
     * @param iterable<DeadRecordsInterface> $stores
     */
    public function __construct(
        private iterable $stores,
        private ClockInterface $clock,
        private int $perRun = self::PER_RUN,
    ) {
    }

    public function removeDead(Tenant $tenant): int
    {
        $now = $this->clock->now();
        $forgotten = [];
        foreach ($this->stores as $store) {
            $forgotten[] = $store->forgetWhatIsDead($tenant, $now, $this->perRun);
        }

        return array_sum($forgotten);
    }
}
