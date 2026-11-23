<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Journal\JournalEntry;

interface JournalInterface
{
    /**
     * The act takes the next position of the chain of the journal of its tenant.
     */
    public function record(JournalEntry $entry): void;
}
