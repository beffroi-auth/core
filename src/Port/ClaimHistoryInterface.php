<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\ClaimChange;

/**
 * Not a message: a change of a record and its history commit together, in one unit of work.
 */
interface ClaimHistoryInterface
{
    /**
     * The change takes the next position of the chain of the history of its tenant.
     */
    public function record(ClaimChange $change): void;
}
