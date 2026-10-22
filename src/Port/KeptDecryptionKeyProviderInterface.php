<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\KeptEncryptionAlgorithmInterface;
use Beffroi\Core\Domain\Key\UnsealedKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;

/**
 * What opens what a peer encrypted to a key the tenant keeps out of its JWK Set: the key of that algorithm the caller
 * names by its "kid", opened by the KMS.
 *
 * Apart from DecryptionKeyProviderInterface, whose keys are those the JWK Set publishes for a client to encrypt a JWE
 * to: a key kept is never handed to what opens a JWE, nor a key of the set to what opens anything else (decision 0020).
 * The algorithm is asked with the "kid" because the algorithm is what a key belongs to: the key of one SAML identity
 * provider is never answered to a caller asking for the keys of another, whatever "kid" it names.
 */
interface KeptDecryptionKeyProviderInterface
{
    /**
     * @throws KeyNotFoundException when the tenant holds no key under that identifier, holds one of another algorithm
     *                              or of the other use, or held one that has retired since
     * @throws KmsUnavailableException when the KMS does not answer to open the key, which is not open yet
     */
    public function keptDecryptionKey(Tenant $tenant, KeptEncryptionAlgorithmInterface $algorithm, string $kid): UnsealedKey;
}
