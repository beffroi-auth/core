<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use DateInterval;
use Heidi\Core\Domain\Tenant\Tenant;

/**
 * A kind of JWS the tenant signs and how long one stays verifiable; the rotation dates a retirement from
 * the longest (architecture §3.3). Null when the tenant does not issue it.
 */
interface IssuedJwsKindInterface
{
    public function longestLifetime(Tenant $tenant): ?DateInterval;
}
