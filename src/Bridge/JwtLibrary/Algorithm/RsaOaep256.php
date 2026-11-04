<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary\Algorithm;

use Beffroi\Core\Bridge\JwtLibrary\JoseKeyEncryptionAlgorithmInterface;
use Beffroi\Core\Domain\Key\KeyUse;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Algorithm\KeyEncryption\RSAOAEP256 as JoseRsaOaep256;
use Jose\Component\Encryption\Algorithm\KeyEncryptionAlgorithm as JoseAlgorithm;
use Jose\Component\KeyManagement\JWKFactory;

/**
 * RFC 7518 §4.3
 */
final readonly class RsaOaep256 implements JoseKeyEncryptionAlgorithmInterface
{
    public function __construct(
        private RsaKeyBits $keyBits = new RsaKeyBits(),
    ) {
    }

    public function name(): string
    {
        return 'RSA-OAEP-256';
    }

    public function keyType(): string
    {
        return 'RSA';
    }

    public function keyUse(): KeyUse
    {
        return KeyUse::Encryption;
    }

    public function joseAlgorithm(): JoseAlgorithm
    {
        return new JoseRsaOaep256();
    }

    public function generateKey(): JWK
    {
        return JWKFactory::createRSAKey($this->keyBits->value, [
            'alg' => $this->name(),
            'use' => $this->keyUse()->value,
        ]);
    }
}
