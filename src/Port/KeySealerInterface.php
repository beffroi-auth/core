<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KmsUnavailableException;
use Beffroi\Core\Exception\RuntimeException;
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
     * @throws KmsUnavailableException when the KMS of the tenant does not answer or refuses to seal
     * @throws RuntimeException when the envelope cannot be made for another reason
     * @return string opaque bytes, safe to store
     */
    public function seal(Tenant $tenant, #[SensitiveParameter] array $privateJwk): string;

    /**
     * @throws KmsUnavailableException when the KMS of the tenant does not answer or refuses to open
     * @throws RuntimeException when the envelope was not sealed by this use for this tenant, or holds no JWK
     * @return array<string, string>
     */
    public function unseal(Tenant $tenant, #[SensitiveParameter] string $sealed): array;
}
