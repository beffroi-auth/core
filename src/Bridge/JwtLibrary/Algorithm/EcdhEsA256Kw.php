<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary\Algorithm;

use Beffroi\Core\Bridge\JwtLibrary\JoseKeyEncryptionAlgorithmInterface;
use Beffroi\Core\Domain\Key\KeyUse;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Algorithm\KeyEncryption\ECDHESA256KW as JoseEcdhEsA256Kw;
use Jose\Component\Encryption\Algorithm\KeyEncryptionAlgorithm as JoseAlgorithm;
use Jose\Component\KeyManagement\JWKFactory;

/**
 * RFC 7518 §4.6
 */
final readonly class EcdhEsA256Kw implements JoseKeyEncryptionAlgorithmInterface
{
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
