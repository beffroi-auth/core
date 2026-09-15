<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use SensitiveParameter;

/**
 * Seals a private JWK so that it can be stored, and opens it back in memory. The KMS keeps, it does not sign
 * (architecture §3.3): changing the KMS changes the sealer's configuration, not this contract.
 */
interface KeySealerInterface
{
    /**
     * @param array<string, string> $privateJwk
     *
     * @return string opaque bytes, safe to store
     */
    public function seal(#[SensitiveParameter] array $privateJwk): string;

    /**
     * @return array<string, string>
     */
    public function unseal(#[SensitiveParameter] string $sealed): array;
}
