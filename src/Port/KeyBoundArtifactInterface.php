<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use DateInterval;
use Beffroi\Core\Domain\Key\KeyUse;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * A kind of artifact bound to a key: a retiring key waits for the longest lifetime among the kinds of its use.
 */
interface KeyBoundArtifactInterface
{
    public function keyUse(): KeyUse;

    public function longestLifetime(Tenant $tenant): ?DateInterval;
}
