<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\ClaimChange;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * Where what a claim of a person used to say is written down and forgotten again (issue #482). Both are
 * writes, which is why they are one port; reading them is another (ChangesOfAPersonInterface).
 *
 * The write is not a message. A change of a record and the history of that change commit together or the
 * record changed and its history did not, which is a hole in the one thing a history is for: the use case
 * writes both (`SelfDescription`), in the unit of work it already has.
 */
interface ClaimHistoryInterface
{
    public function record(ClaimChange $change): void;

    /**
     * Forgets the changes of this tenant older than the moment given, and says how many it forgot.
     * Bounded by the implementation, so a backlog is worked through over several runs.
     */
    public function forgetBefore(Tenant $tenant, DateTimeImmutable $moment): int;
}
