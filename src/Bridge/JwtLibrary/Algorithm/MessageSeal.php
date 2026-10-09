<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary\Algorithm;

use Beffroi\Core\Bridge\JwtLibrary\JoseContentEncryptionAlgorithmInterface;
use Beffroi\Core\Bridge\JwtLibrary\JoseKeyEncryptionAlgorithmInterface;
use Beffroi\Core\Bridge\JwtLibrary\JoseMessageSealingAlgorithmInterface;
use Beffroi\Core\Domain\Key\KeyUse;
use Jose\Component\Core\JWK;

/**
 * The key pair of a tenant that what it writes to a transport is sealed to (decision 0020): the public
 * half seals, on the path of a request and without asking the KMS anything, and the private half opens, in
 * the worker that consumes.
 *
 * "MESSAGE-SEAL" is what this tenant calls the pair and not a JOSE algorithm name, for the reason the key
 * it hands out to browsers has one of its own (Vapid): a tenant holds one active key per algorithm
 * (TenantKeyRepositoryInterface::findActive()), so a pair answering to "ECDH-ES+A256KW" would be the pair
 * clients encrypt to, which the JWK Set publishes, sealing the bodies of this provider. What the sealed
 * body carries is the JOSE pair below, both wired, so nothing is sealed that this provider would refuse
 * to open.
 *
 * The pair is of the shape of the key management algorithm it seals under, which is what draws it: one
 * algorithm decides the curve, the size and the "kty", here as everywhere else.
 */
final readonly class MessageSeal implements JoseMessageSealingAlgorithmInterface
{
    public function __construct(
        private JoseKeyEncryptionAlgorithmInterface $keyEncryption,
        private JoseContentEncryptionAlgorithmInterface $contentEncryption,
    ) {
    }

    public function name(): string
    {
        return 'MESSAGE-SEAL';
    }

    public function keyType(): string
    {
        return $this->keyEncryption->keyType();
    }

    /**
     * RFC 7517 §4.2: what the pair does is receive a JWE. It is asserted against the algorithm like every
     * other key of the tenant, and it says nothing of whether the tenant publishes it, which is the
     * business of PublishedKeyAlgorithmInterface.
     */
    public function keyUse(): KeyUse
    {
        return KeyUse::Encryption;
    }

    public function keyEncryption(): JoseKeyEncryptionAlgorithmInterface
    {
        return $this->keyEncryption;
    }

    public function contentEncryption(): JoseContentEncryptionAlgorithmInterface
    {
        return $this->contentEncryption;
    }

    /**
     * The pair the wired key management algorithm draws, under the name of this one: what the JWK carries
     * in its "alg" is what the tenant calls the pair, so a key read back from the store is read back as
     * the key it is and not as one clients encrypt to.
     */
    public function generateKey(): JWK
    {
        return new JWK([
            ...$this->keyEncryption->generateKey()->all(),
            'alg' => $this->name(),
            'use' => $this->keyUse()->value,
        ]);
    }
}
