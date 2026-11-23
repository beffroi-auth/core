<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Chain\Checkpoint;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * Where a signed head leaves the instance, so that a copy outlives whoever holds the database and the key storage.
 */
interface CheckpointCopiesInterface
{
    public function copy(Tenant $tenant, Checkpoint $checkpoint, string $compactJws): void;
}
