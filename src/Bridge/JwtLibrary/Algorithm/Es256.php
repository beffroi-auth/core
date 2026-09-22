<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary\Algorithm;

use Heidi\Core\Bridge\JwtLibrary\JoseSignatureAlgorithmInterface;
use Heidi\Core\Domain\Key\KeyUse;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\ES256 as JoseEs256;
use Jose\Component\Signature\Algorithm\SignatureAlgorithm as JoseAlgorithm;

/**
 * ECDSA over P-256 with SHA-256 (RFC 7518 §3.4), the elliptic curve algorithm FAPI 2.0 §5.2.2 takes; its
 * signatures and its keys are the shortest of the four.
 */
final readonly class Es256 implements JoseSignatureAlgorithmInterface
{
    /**
     * RFC 7518 §6.2.1.1: the curve ES256 signs over, and the only one it signs over.
     */
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
