<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtFramework;

use Heidi\Core\Domain\Key\GeneratedKeyPair;
use Heidi\Core\Domain\Key\SignatureAlgorithm;
use Heidi\Core\Exception\InvalidArgumentException;
use Heidi\Core\Port\KeyPairGeneratorInterface;
use function is_string;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use function sprintf;

/**
 * Generates key pairs with jwt-framework. The kid is the RFC 7638 SHA-256 thumbprint of the public key, so
 * that two providers never mint the same kid for different keys and a key is recognisable by its material.
 */
final readonly class JwtFrameworkKeyPairGenerator implements KeyPairGeneratorInterface
{
    /**
     * 3072 bits by default: RFC 7518 §3.3 requires at least 2048, NIST SP 800-57 Part 1 Rev. 5 §5.6 rates
     * 3072 at 128 bits of security. A deployment may raise it, never lower it under the algorithm's minimum.
     */
    public function __construct(
        private int $rsaKeyBits = 3072,
    ) {
        if ($rsaKeyBits < SignatureAlgorithm::RS256->minimumKeyBits() || $rsaKeyBits % 8 !== 0) {
            throw new InvalidArgumentException(sprintf('RSA keys are at least %d bits, in whole bytes.', SignatureAlgorithm::RS256->minimumKeyBits()));
        }
    }

    public function generate(SignatureAlgorithm $algorithm): GeneratedKeyPair
    {
        $private = match ($algorithm) {
            SignatureAlgorithm::RS256 => JWKFactory::createRSAKey($this->rsaKeyBits, [
                'alg' => $algorithm->value,
                'use' => 'sig',
            ]),
        };
        $kid = $private->thumbprint('sha256');
        $private = new JWK([
            ...$private->all(),
            'kid' => $kid,
        ]);

        return new GeneratedKeyPair($kid, $algorithm, self::members($private), self::members($private->toPublic()));
    }

    /**
     * @return array<string, string>
     */
    private static function members(JWK $jwk): array
    {
        $members = [];
        foreach ($jwk->all() as $member => $value) {
            if (is_string($member) && is_string($value)) {
                $members[$member] = $value;
            }
        }

        return $members;
    }
}
