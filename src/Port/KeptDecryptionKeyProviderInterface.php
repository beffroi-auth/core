<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\KeptEncryptionAlgorithmInterface;
use Beffroi\Core\Domain\Key\UnsealedKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;

/**
 * What opens what a peer encrypted to a key the tenant keeps out of its JWK Set: the keys of that algorithm the caller
 * names by their "kid", opened by the KMS, every other key of that algorithm the process holds forgotten.
 *
 * Apart from DecryptionKeyProviderInterface, whose keys are those the JWK Set publishes for a client to encrypt a JWE
 * to: a key kept is never handed to what opens a JWE, nor a key of the set to what opens anything else (decision 0020).
 * The algorithm is asked with the "kid" because the algorithm is what a key belongs to: the key of one SAML identity
 * provider is never answered to a caller asking for the keys of another, whatever "kid" it names.
 */
interface KeptDecryptionKeyProviderInterface
{
    /**
     * The keys named, opened, in the order named; a key of that algorithm the process holds open and the caller does
     * not name is forgotten, so that a key that has retired since the last call does not stay in the process. None
     * named forgets them all.
     *
     * @param list<string> $kids the keys in service of that algorithm, as the caller read them
     *
     * @throws KeyNotFoundException when the tenant holds no key under one of those identifiers, holds one of another
     *                              algorithm or of the other use, or held one that has retired since
     * @throws KmsUnavailableException when the KMS does not answer to open a key, which is not open yet
     *
     * @return list<UnsealedKey>
     */
    public function keptDecryptionKeys(Tenant $tenant, KeptEncryptionAlgorithmInterface $algorithm, array $kids): array;
}
