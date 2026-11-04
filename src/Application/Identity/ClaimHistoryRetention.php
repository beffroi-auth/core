<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Domain\Listing\RetentionWindow;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\ClaimHistoryInterface;
use Psr\Clock\ClockInterface;

final readonly class ClaimHistoryRetention
{
    public function __construct(
        private ClaimHistoryInterface $history,
        private ClockInterface $clock,
        private RetentionWindow $window,
    ) {
    }

    public function enforce(Tenant $tenant): int
    {
        return $this->history->forgetBefore($tenant, $this->window->keptSince($this->clock->now()));
    }
}
