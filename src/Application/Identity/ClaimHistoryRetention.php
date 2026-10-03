<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\ClaimHistoryInterface;
use Psr\Clock\ClockInterface;
use function sprintf;

/**
 * How long the history of what a record used to say keeps a change, and what forgets the ones past it
 * (issue #482).
 *
 * A history of what a person used to be called is a personal datum with a duration and not a thing kept
 * for ever. The duration is the deployment's, and what it says is read here and nowhere else.
 *
 * It says how many it forgot, so a timed step can say whether it did anything, and the store bounds a run
 * so that a backlog is worked through over several ticks.
 */
final readonly class ClaimHistoryRetention
{
    public function __construct(
        private ClaimHistoryInterface $history,
        private ClockInterface $clock,
        private int $days,
    ) {
    }

    public function enforce(Tenant $tenant): int
    {
        return $this->history->forgetBefore($tenant, $this->clock->now()->modify(sprintf('-%d days', $this->days)));
    }
}
