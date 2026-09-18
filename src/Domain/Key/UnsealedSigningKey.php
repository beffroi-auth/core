<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Key;

use SensitiveParameter;

/**
 * A signing key with its private JWK in clear, in memory only.
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
