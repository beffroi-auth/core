<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * What a store may drop of its own rows, once they can no longer do anything (issue #177).
 *
 * **Every store answers for itself.** Why a row is kept past the moment it stops working is written where
 * that row is described, not here: a spent authorization code tells a replay from a code nobody issued, a
 * rotated refresh token tells a reuse from a token nobody issued, and each of them stops being worth
 * anything at an instant only the store that keeps it can name. So there is no central table of durations
 * and no class that knows the tables: a kind of row the provider starts keeping is a store wired here, and
 * never a branch added to the step.
 *
 * It removes rather than answers, where the removal of the clients nobody uses any more answers
 * (UnusedClientsInterface): a client leaving says so with an event whoever reads the journal will find, and
 * a row that can no longer do anything is not an act of anybody.
 */
interface DeadRecordsInterface
{
    /**
     * Forgets the rows of this tenant that can no longer do anything, and says how many it forgot.
     *
     * @param int $limit how many rows one statement of this store may drop, at most, so that no statement
     *                   holds a table on a large deployment; a store that sweeps more than one table makes
     *                   one bounded statement per table, and what a run leaves the next run takes
     *
     * @return int how many rows it dropped, zero when it found nothing
     */
    public function forgetWhatIsDead(Tenant $tenant, DateTimeImmutable $now, int $limit): int;
}
