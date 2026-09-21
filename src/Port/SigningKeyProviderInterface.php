<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Key\PublicKeySet;
use Heidi\Core\Domain\Key\SignatureAlgorithmInterface;
use Heidi\Core\Domain\Key\UnsealedSigningKey;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\KeyNotFoundException;
use Heidi\Core\Exception\KmsUnavailableException;

interface SigningKeyProviderInterface
{
    /**
     * @throws KeyNotFoundException when the tenant has no active key for the algorithm
     * @throws KmsUnavailableException when the KMS does not answer to open the active key, which is not open yet
     */
    public function activeKey(Tenant $tenant, SignatureAlgorithmInterface $algorithm): UnsealedSigningKey;

    public function publicKeys(Tenant $tenant): PublicKeySet;
}
