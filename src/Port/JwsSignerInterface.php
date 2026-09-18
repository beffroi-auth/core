<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Key\SignatureAlgorithm;
use Heidi\Core\Domain\Tenant\Tenant;

interface JwsSignerInterface
{
    /**
     * The compact serialization (RFC 7515 §7.1); "alg" and "kid" are set here, the rest by the caller.
     *
     * @param array<string, mixed> $protectedHeader
     */
    public function sign(Tenant $tenant, SignatureAlgorithm $algorithm, string $payload, array $protectedHeader = []): string;
}
