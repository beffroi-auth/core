<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Key;

/**
 * A compact JWS whose signature was verified against a published key of the tenant.
 */
final readonly class VerifiedJws
{
    /**
     * @param array<string, mixed> $protectedHeader
     */
    public function __construct(
        public TenantKey $key,
        public array $protectedHeader,
        public string $payload,
    ) {
    }
}
