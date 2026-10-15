<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\KeptSigningAlgorithmInterface;
use Beffroi\Core\Domain\Key\UnsealedKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;

/**
 * What signs with a key the tenant keeps out of its JWK Set: the active key of that algorithm, opened by the KMS.
 *
 * Apart from SigningKeyProviderInterface, whose algorithms are those a JWS names and its JWK Set publishes: a key
 * kept is never handed to what signs a JWS, nor a key of the set to what signs anything else, the two capabilities
 * being two ports and their algorithms two families (decision 0020).
 */
interface KeptSigningKeyProviderInterface
{
    /**
     * @throws KeyNotFoundException when the tenant has no active key for the algorithm, or one that does not sign
     * @throws KmsUnavailableException when the KMS does not answer to open the active key, which is not open yet
     */
    public function activeKeptSigningKey(Tenant $tenant, KeptSigningAlgorithmInterface $algorithm): UnsealedKey;
}
