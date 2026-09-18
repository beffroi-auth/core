<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Tenant\Tenant;
use SensitiveParameter;

/**
 * Seals a private JWK for storage and opens it back in memory (architecture §3.3). The envelope is bound
 * to the tenant: sealed for one, it does not open for another.
 */
interface KeySealerInterface
{
    /**
     * @param array<string, string> $privateJwk
     *
     * @return string opaque bytes, safe to store
     */
    public function seal(Tenant $tenant, #[SensitiveParameter] array $privateJwk): string;

    /**
     * @return array<string, string>
     */
    public function unseal(Tenant $tenant, #[SensitiveParameter] string $sealed): array;
}
