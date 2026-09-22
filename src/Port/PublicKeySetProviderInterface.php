<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Key\PublicKeySet;
use Heidi\Core\Domain\Tenant\Tenant;

/**
 * What the tenant publishes: the JWK Set endpoint serves it whole, a verifier asks it for the keys that
 * sign. No private material is opened here, so nothing of this depends on the KMS.
 */
interface PublicKeySetProviderInterface
{
    public function publicKeys(Tenant $tenant): PublicKeySet;
}
