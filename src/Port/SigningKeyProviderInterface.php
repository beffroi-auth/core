<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Key\SignatureAlgorithmInterface;
use Heidi\Core\Domain\Key\UnsealedKey;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\KeyNotFoundException;
use Heidi\Core\Exception\KmsUnavailableException;

/**
 * What signs: the active key of the tenant for one signature algorithm, opened by the KMS.
 */
interface SigningKeyProviderInterface
{
    /**
     * @throws KeyNotFoundException when the tenant has no active key for the algorithm
     * @throws KmsUnavailableException when the KMS does not answer to open the active key, which is not open yet
     */
    public function activeKey(Tenant $tenant, SignatureAlgorithmInterface $algorithm): UnsealedKey;
}
