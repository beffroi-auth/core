<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Archive;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\PersonalArchiveRepositoryInterface;
use Psr\Clock\ClockInterface;

/**
 * What forgets the archives that have expired (issue #453).
 *
 * It reads no duration: every row carries the date it goes away on, set when the person asked for it
 * (PersonalArchive::KEPT_FOR_DAYS). There is nothing for a deployment to say here, which is the point: the
 * one file where every sealed column stands in clear at once is not kept for as long as somebody felt like.
 *
 * It says how many it forgot, so a timed step can say whether it did anything, and the store bounds a run
 * so that a backlog is worked through over several ticks.
 */
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
