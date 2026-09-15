<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Key\SignatureAlgorithm;
use Heidi\Core\Domain\Tenant\Tenant;

interface JwsSignerInterface
{
    /**
     * Signs a payload with the active key of the tenant and returns the compact serialization (RFC 7515 §7.1).
     * The protected header always carries "alg" and "kid"; "typ" and the rest come from the caller.
     *
     * @param array<string, mixed> $protectedHeader
     */
    public function sign(Tenant $tenant, SignatureAlgorithm $algorithm, string $payload, array $protectedHeader = []): string;
}
