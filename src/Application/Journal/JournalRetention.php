<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Journal;

use Beffroi\Core\Domain\Listing\RetentionWindow;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\JournalInterface;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

final readonly class JournalRetention
{
    public function __construct(
        private JournalInterface $journal,
        private ClockInterface $clock,
        public RetentionWindow $window,
    ) {
    }

    public function enforce(Tenant $tenant): int
    {
        return $this->journal->forgetBefore($tenant, $this->keptSince());
    }

    public function keptSince(): DateTimeImmutable
    {
        return $this->window->keptSince($this->clock->now());
    }
}
