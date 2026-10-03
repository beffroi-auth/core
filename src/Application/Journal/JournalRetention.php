<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Journal;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\JournalInterface;
use function sprintf;
use Psr\Clock\ClockInterface;

/**
 * How long the journal keeps an act, and what forgets the ones past it (issue #454).
 *
 * Six months is the default of the deployment, which is the duration the CNIL's deliberation 2021-122
 * holds for a log of connections, and it is a duration and not a rule written in code: a deployment under
 * another obligation says its own, and what it says is read here and nowhere else.
 *
 * It says how many it forgot, so a timed step can say whether it did anything, and the store bounds a run
 * so that a backlog is worked through over several ticks rather than in one statement that holds a table.
 */
final readonly class JournalRetention
{
    public function __construct(
        private JournalInterface $journal,
        private ClockInterface $clock,
        private int $days,
    ) {
    }

    public function enforce(Tenant $tenant): int
    {
        return $this->journal->forgetBefore($tenant, $this->clock->now()->modify(sprintf('-%d days', $this->days)));
    }
}
