<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Key;

use SensitiveParameter;

/**
 * What a key pair generator returns: the private JWK, the public JWK (with kid, alg and use set) and the
 * kid, an RFC 7638 thumbprint.
 */
final readonly class GeneratedKeyPair
{
    /**
     * @param array<string, string> $privateJwk
     * @param array<string, string> $publicJwk
     */
    public function __construct(
        public string $kid,
        public SignatureAlgorithm $algorithm,
        #[SensitiveParameter]
        public array $privateJwk,
        public array $publicJwk,
    ) {
    }
}
