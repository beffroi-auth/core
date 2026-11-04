<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary\Algorithm;

use Beffroi\Core\Bridge\JwtLibrary\JoseSignatureAlgorithmInterface;
use Beffroi\Core\Domain\Key\KeyUse;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\ES256 as JoseEs256;
use Jose\Component\Signature\Algorithm\SignatureAlgorithm as JoseAlgorithm;

/**
 * RFC 7518 §3.4
 */
final readonly class Es256 implements JoseSignatureAlgorithmInterface
{
    public const string CURVE = 'P-256';

    public function name(): string
    {
        return 'ES256';
    }

    public function keyType(): string
    {
        return 'EC';
    }

    public function keyUse(): KeyUse
    {
        return KeyUse::Signature;
    }

    public function digestAlgorithm(): string
    {
        return 'sha256';
    }

    public function joseAlgorithm(): JoseAlgorithm
    {
        return new JoseEs256();
    }

    public function generateKey(): JWK
    {
        return JWKFactory::createECKey(self::CURVE, [
            'alg' => $this->name(),
            'use' => $this->keyUse()->value,
        ]);
    }
}
