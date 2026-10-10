<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Record;

use function array_sum;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\DeadRecordsInterface;
use Psr\Clock\ClockInterface;

/**
 * The hygiene of the tables this provider writes to without anybody asking it to (issue #177): a row that
 * can no longer do anything is dropped. Everything the protocol makes the provider write is kept past the
 * moment it stops working, so that a second presentation is told from a value nobody ever issued; past
 * that, it is a table that only grows at whatever rate its clients post, and nobody was ever told to watch
 * it.
 *
 * It asks **each store what it may drop** and knows no table: what "no longer" means is the store's, the
 * reason being written where the row is described (DeadRecordsInterface). A kind of row the provider starts
 * keeping is a store wired beside the others, never a branch here.
 *
 * **What is bounded is a statement, and the bound is per store**, so that neither a table with a backlog
 * nor a store that sweeps several of them keeps the others from being swept. It is recurring and
 * idempotent, so what one run leaves the next takes.
 */
final readonly class DeadRecordRemoval
{
    /**
     * How many rows one statement of a store drops. Larger than the hundred a client removal takes, these
     * rows being small, numerous and keyed by a digest; small enough that the delete is not a statement
     * that holds a table while a login waits on it.
     */
    public const int PER_RUN = 1000;

    /**
     * @param iterable<DeadRecordsInterface> $stores the stores that hold rows, in the order they are wired
     */
    public function __construct(
        private iterable $stores,
        private ClockInterface $clock,
        private int $perRun = self::PER_RUN,
    ) {
    }

    /**
     * @return int how many rows it dropped, zero when every store found nothing
     */
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
