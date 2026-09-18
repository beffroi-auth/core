<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Key\PublicKeySet;
use Heidi\Core\Domain\Key\SignatureAlgorithm;
use Heidi\Core\Domain\Key\UnsealedSigningKey;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\KeyNotFoundException;

interface SigningKeyProviderInterface
{
    /**
     * @throws KeyNotFoundException when the tenant has no active key for the algorithm
     */
    public function activeKey(Tenant $tenant, SignatureAlgorithm $algorithm): UnsealedSigningKey;

    public function publicKeys(Tenant $tenant): PublicKeySet;
}
