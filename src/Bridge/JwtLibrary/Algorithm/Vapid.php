<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary\Algorithm;

use Beffroi\Core\Bridge\JwtLibrary\JoseApplicationServerAlgorithmInterface;
use Beffroi\Core\Domain\Key\KeyUse;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;

/**
 * The application server key pair of Voluntary Application Server Identification (RFC 8292 §2): ECDSA
 * over P-256, which §3.2 makes the only curve a push service takes, and which the browser is handed as
 * the uncompressed point of §3.2 when it subscribes.
 *
 * "VAPID" is what this tenant calls the pair and not a JOSE algorithm name: the JWT the provider signs
 * with it carries "alg": "ES256" as §2 requires, and this name exists so that the key pair the provider
 * hands to browsers is never the key pair it signs tokens with. A tenant holds one active key of each
 * algorithm (TenantKeyRepositoryInterface::findActive()), and two pairs that answered to the same name
 * would be one pair doing both jobs.
 */
final readonly class Vapid implements JoseApplicationServerAlgorithmInterface
{
    /**
     * RFC 8292 §3.2: the curve a push service takes, and the only one.
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

    /**
     * RFC 7517 §4.2: what the pair does is sign, whoever reads the signature. It is asserted against the
     * algorithm like every other key of the tenant, and it says nothing about who is handed the public
     * half, which is the business of ApplicationServerAlgorithmInterface.
     */
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
