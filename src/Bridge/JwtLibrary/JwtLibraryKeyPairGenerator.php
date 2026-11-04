<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Key\GeneratedKeyPair;
use Beffroi\Core\Domain\Key\KeyAlgorithmInterface;
use Beffroi\Core\Port\KeyPairGeneratorInterface;
use function is_string;
use Jose\Component\Core\JWK;

final readonly class JwtLibraryKeyPairGenerator implements KeyPairGeneratorInterface
{
    public function __construct(
        private JoseKeyAlgorithms $algorithms,
    ) {
    }

    public function generate(KeyAlgorithmInterface $algorithm): GeneratedKeyPair
    {
        $private = $this->algorithms->named($algorithm->name())->generateKey();
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
