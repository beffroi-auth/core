<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Domain\Listing\RetentionWindow;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

final readonly class ClaimHistoryRetention
{
    public function __construct(
        private ClockInterface $clock,
        public RetentionWindow $window,
    ) {
    }

    public function keptSince(): DateTimeImmutable
    {
        return $this->window->keptSince($this->clock->now());
    }
}
