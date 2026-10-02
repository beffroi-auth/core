<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use DateInterval;
use Beffroi\Core\Domain\Key\KeyUse;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * A kind of artifact bound to a key of the tenant and how long one stays usable; the rotation dates a
 * retirement from the longest among the kinds that name the use of the key retiring (architecture §3.3).
 * A JWS the provider signed stays verifiable while it is alive, and a key a client may still be
 * encrypting to stays openable while such a message may arrive: the two are the same window read on
 * either side. Null when the tenant does not issue it, or does not read it.
 *
 * A kind declares itself and its use; nothing enumerates the kinds, and no lifetime is written twice.
 */
interface KeyBoundArtifactInterface
{
    /**
     * Which keys of the tenant this kind holds back.
     */
    public function keyUse(): KeyUse;

    public function longestLifetime(Tenant $tenant): ?DateInterval;
}
