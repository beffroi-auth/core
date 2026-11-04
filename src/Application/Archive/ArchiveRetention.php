<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Archive;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\PersonalArchiveRepositoryInterface;
use Psr\Clock\ClockInterface;

final readonly class ArchiveRetention
{
    public function __construct(
        private PersonalArchiveRepositoryInterface $archives,
        private ClockInterface $clock,
    ) {
    }

    public function enforce(Tenant $tenant): int
    {
        return $this->archives->forgetExpired($tenant, $this->clock->now());
    }
}
