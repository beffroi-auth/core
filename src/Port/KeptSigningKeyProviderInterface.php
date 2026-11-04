<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\KeptSigningAlgorithmInterface;
use Beffroi\Core\Domain\Key\UnsealedKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;

/**
 * A key the tenant keeps never signs a JWS, nor a key of its JWK Set anything else.
 */
interface KeptSigningKeyProviderInterface
{
    /**
     * @throws KeyNotFoundException
     * @throws KmsUnavailableException
     */
    public function activeKeptSigningKey(Tenant $tenant, KeptSigningAlgorithmInterface $algorithm): UnsealedKey;
}
