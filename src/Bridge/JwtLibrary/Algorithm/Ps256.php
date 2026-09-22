<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary\Algorithm;

use Heidi\Core\Bridge\JwtLibrary\JoseSignatureAlgorithmInterface;
use Heidi\Core\Domain\Key\KeyUse;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\PS256 as JosePs256;
use Jose\Component\Signature\Algorithm\SignatureAlgorithm as JoseAlgorithm;

/**
 * RSASSA-PSS with SHA-256 and MGF1 with SHA-256 (RFC 7518 §3.5), the RSA algorithm FAPI 2.0 §5.2.2 takes.
 * Same key pairs as RS256, another signature scheme: a tenant that signs in both holds one key per
 * algorithm all the same, since a JWK names the one algorithm it is for (RFC 7517 §4.4).
 */
final readonly class Ps256 implements JoseSignatureAlgorithmInterface
{
    public function __construct(
        private RsaKeyBits $keyBits = new RsaKeyBits(),
    ) {
    }

    public function name(): string
    {
        return 'PS256';
    }

    public function keyType(): string
    {
        return 'RSA';
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
        return new JosePs256();
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
