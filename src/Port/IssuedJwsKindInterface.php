<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use DateInterval;
use Heidi\Core\Domain\Tenant\Tenant;

/**
 * A kind of JWS the tenant signs with its keys (an ID token, a self-contained access token, whatever a
 * module adds), and the longest one stays verifiable once issued. The rotation asks every kind before it
 * dates the retirement of a key (architecture §3.3): the key leaves the JWK Set once the last JWS it could
 * have signed has expired. What is not a JWS (an opaque token) is not a kind; what the tenant does not
 * issue answers null and does not count.
 */
interface IssuedJwsKindInterface
{
    public function longestLifetime(Tenant $tenant): ?DateInterval;
}
