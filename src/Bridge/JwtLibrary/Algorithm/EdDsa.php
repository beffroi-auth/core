<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary\Algorithm;

use Beffroi\Core\Bridge\JwtLibrary\JoseSignatureAlgorithmInterface;
use Beffroi\Core\Domain\Key\KeyUse;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\EdDSA as JoseEdDsa;
use Jose\Component\Signature\Algorithm\SignatureAlgorithm as JoseAlgorithm;

/**
 * EdDSA over Ed25519 (RFC 8037 §3.1), the curve published in the JWK Set as "crv": "Ed25519" with a "kty"
 * of "OKP". Ed25519 signs over SHA-512 (RFC 8032 §5.1), which is the digest "at_hash" halves (OIDC Core
 * §3.1.3.6 reads the hash from the "alg").
 */
final readonly class EdDsa implements JoseSignatureAlgorithmInterface
{
    /**
     * RFC 8037 §3.1: the one curve this provider signs EdDSA over; Ed448 is not wired.
     */
    public const string CURVE = 'Ed25519';

    public function name(): string
    {
        return 'EdDSA';
    }

    public function keyType(): string
    {
        return 'OKP';
    }

    public function keyUse(): KeyUse
    {
        return KeyUse::Signature;
    }

    public function digestAlgorithm(): string
    {
        return 'sha512';
    }

    public function joseAlgorithm(): JoseAlgorithm
    {
        return new JoseEdDsa();
    }

    public function generateKey(): JWK
    {
        return JWKFactory::createOKPKey(self::CURVE, [
            'alg' => $this->name(),
            'use' => $this->keyUse()->value,
        ]);
    }
}
