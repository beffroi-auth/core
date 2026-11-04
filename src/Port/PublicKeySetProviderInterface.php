<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\PublicKeySet;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * Opens no private material, so nothing of it depends on the KMS.
 */
interface PublicKeySetProviderInterface
{
    public function publicKeys(Tenant $tenant): PublicKeySet;
}
