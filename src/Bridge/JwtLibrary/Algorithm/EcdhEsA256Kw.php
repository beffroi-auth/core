<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary\Algorithm;

use Heidi\Core\Bridge\JwtLibrary\JoseKeyEncryptionAlgorithmInterface;
use Heidi\Core\Domain\Key\KeyUse;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Algorithm\KeyEncryption\ECDHESA256KW as JoseEcdhEsA256Kw;
use Jose\Component\Encryption\Algorithm\KeyEncryptionAlgorithm as JoseAlgorithm;
use Jose\Component\KeyManagement\JWKFactory;

/**
 * ECDH-ES over P-256 with the agreed key wrapping the content encryption key under A256KW (RFC 7518 §4.6).
 * The ephemeral key of the sender makes every message its own secret, which is the forward secrecy
 * FAPI 2.0 Message Signing §5.4 asks for, and the key pair is the same shape as the one ES256 signs with.
 */
final readonly class EcdhEsA256Kw implements JoseKeyEncryptionAlgorithmInterface
{
    /**
     * RFC 7518 §6.2.1.1: the curve this provider agrees over, and the only one.
     */
    public const string CURVE = 'P-256';

    public function name(): string
    {
        return 'ECDH-ES+A256KW';
    }

    public function keyType(): string
    {
        return 'EC';
    }

    public function keyUse(): KeyUse
    {
        return KeyUse::Encryption;
    }

    public function joseAlgorithm(): JoseAlgorithm
    {
        return new JoseEcdhEsA256Kw();
    }

    public function generateKey(): JWK
    {
        return JWKFactory::createECKey(self::CURVE, [
            'alg' => $this->name(),
            'use' => $this->keyUse()->value,
        ]);
    }
}
