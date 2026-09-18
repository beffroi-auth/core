<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Tenant\Tenant;
use SensitiveParameter;

/**
 * Seals a private JWK so that it can be stored, and opens it back in memory. The KMS keeps, it does not sign
 * (architecture §3.3): changing the KMS changes the sealer's configuration, not this contract. The tenant
 * names the master key an envelope is sealed under and is bound to the envelope: a thousand tenants have
 * a thousand master keys, and a key sealed for one tenant does not open for another.
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
