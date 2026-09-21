<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary;

use Heidi\Core\Domain\Key\GeneratedKeyPair;
use Heidi\Core\Domain\Key\SignatureAlgorithmInterface;
use Heidi\Core\Port\KeyPairGeneratorInterface;
use function is_string;
use Jose\Component\Core\JWK;

/**
 * Generates key pairs with web-token/jwt-library; each algorithm makes its own key, this turns it into a
 * pair the core stores and publishes. The "kid" is the RFC 7638 SHA-256 thumbprint of the public key, so
 * two keys of the same material carry the same name whoever generated them.
 */
final readonly class JwtLibraryKeyPairGenerator implements KeyPairGeneratorInterface
{
    public function __construct(
        private JoseSignatureAlgorithms $algorithms,
    ) {
    }

    public function generate(SignatureAlgorithmInterface $algorithm): GeneratedKeyPair
    {
        $private = $this->algorithms->named($algorithm->name())
            ->generateKey();
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
