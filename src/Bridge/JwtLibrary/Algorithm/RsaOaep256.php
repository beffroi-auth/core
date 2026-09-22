<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary\Algorithm;

use Heidi\Core\Bridge\JwtLibrary\JoseKeyEncryptionAlgorithmInterface;
use Heidi\Core\Domain\Key\KeyUse;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Algorithm\KeyEncryption\RSAOAEP256 as JoseRsaOaep256;
use Jose\Component\Encryption\Algorithm\KeyEncryptionAlgorithm as JoseAlgorithm;
use Jose\Component\KeyManagement\JWKFactory;

/**
 * RSAES OAEP with SHA-256 (RFC 7518 §4.3), the algorithm a client that has no elliptic curve stack
 * encrypts with. RSA1_5 is not wired anywhere (RFC 8725 §3.4), and the modulus is the one the RSA
 * signature algorithms take, so the rule on key sizes is written once.
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
            'use' => $this->keyUse()
                ->value,
        ]);
    }
}
