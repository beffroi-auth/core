<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

use SensitiveParameter;

/**
 * The kid is an RFC 7638 thumbprint.
 */
final readonly class GeneratedKeyPair
{
    /**
     * @param array<string, string> $privateJwk
     * @param array<string, string> $publicJwk
     */
    public function __construct(
        public string $kid,
        public KeyAlgorithmInterface $algorithm,
        #[SensitiveParameter]
        public array $privateJwk,
        public array $publicJwk,
    ) {
    }
}
