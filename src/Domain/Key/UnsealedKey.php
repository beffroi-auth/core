<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

use SensitiveParameter;

/**
 * A key of a tenant with its private JWK in clear, in memory only: what signs, and what opens a JWE.
 */
final readonly class UnsealedKey
{
    /**
     * @param array<string, string> $privateJwk
     */
    public function __construct(
        public TenantKey $key,
        #[SensitiveParameter]
        public array $privateJwk,
    ) {
    }
}
