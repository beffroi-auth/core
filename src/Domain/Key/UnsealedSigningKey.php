<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Key;

use SensitiveParameter;

/**
 * A signing key with its private JWK in clear, in memory only. Handed by the key provider to the JWS bridge
 * and to nothing else.
 */
final readonly class UnsealedSigningKey
{
    /**
     * @param array<string, string> $privateJwk
     */
    public function __construct(
        public SigningKey $key,
        #[SensitiveParameter]
        public array $privateJwk,
    ) {
    }
}
