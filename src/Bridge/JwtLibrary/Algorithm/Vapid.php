<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary\Algorithm;

use Beffroi\Core\Bridge\JwtLibrary\JoseApplicationServerAlgorithmInterface;
use Beffroi\Core\Domain\Key\KeyUse;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;

/**
 * RFC 8292 §2
 *
 * "VAPID" is no JOSE algorithm name: a tenant holds one active key per algorithm, and a pair named "ES256"
 * would be the one it signs tokens with.
 */
final readonly class Vapid implements JoseApplicationServerAlgorithmInterface
{
    /**
     * RFC 8292 §3.2
     */
    public const string CURVE = 'P-256';

    public function name(): string
    {
        return 'VAPID';
    }

    public function keyType(): string
    {
        return 'EC';
    }

    public function keyUse(): KeyUse
    {
        return KeyUse::Signature;
    }

    public function generateKey(): JWK
    {
        return JWKFactory::createECKey(self::CURVE, [
            'alg' => $this->name(),
            'use' => $this->keyUse()->value,
        ]);
    }
}
