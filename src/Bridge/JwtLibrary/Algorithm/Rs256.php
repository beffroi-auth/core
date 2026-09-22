<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary\Algorithm;

use Heidi\Core\Bridge\JwtLibrary\JoseSignatureAlgorithmInterface;
use Heidi\Core\Domain\Key\KeyUse;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\RS256 as JoseRs256;
use Jose\Component\Signature\Algorithm\SignatureAlgorithm as JoseAlgorithm;

/**
 * RSASSA-PKCS1-v1_5 with SHA-256 (RFC 7518 §3.3), the algorithm every relying party understands and the
 * only one OIDC Core §15.1 makes mandatory. FAPI 2.0 §5.2.2 refuses it: a deployment that follows that
 * profile wires the others and leaves this one out.
 */
final readonly class Rs256 implements JoseSignatureAlgorithmInterface
{
    public function __construct(
        private RsaKeyBits $keyBits = new RsaKeyBits(),
    ) {
    }

    public function name(): string
    {
        return 'RS256';
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
        return new JoseRs256();
    }

    public function generateKey(): JWK
    {
        return JWKFactory::createRSAKey($this->keyBits->value, [
            'alg' => $this->name(),
            'use' => $this->keyUse()->value,
        ]);
    }
}
