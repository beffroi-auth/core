<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary\Algorithm;

use Beffroi\Core\Bridge\JwtLibrary\JoseContentEncryptionAlgorithmInterface;
use Beffroi\Core\Bridge\JwtLibrary\JoseKeyEncryptionAlgorithmInterface;
use Beffroi\Core\Bridge\JwtLibrary\JoseMessageSealingAlgorithmInterface;
use Beffroi\Core\Domain\Key\KeyUse;
use Jose\Component\Core\JWK;

/**
 * The key pair a tenant seals the bodies it writes to a transport to.
 *
 * "MESSAGE-SEAL" is no JOSE algorithm name: a tenant holds one active key per algorithm, and a pair named
 * "ECDH-ES+A256KW" would be the one the JWK Set publishes for clients to encrypt to.
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
     * The JWK carries the name of this algorithm in its "alg", so that a key read back from the store is never
     * taken for one clients encrypt to.
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
