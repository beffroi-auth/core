<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

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
