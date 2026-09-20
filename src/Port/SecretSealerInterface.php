<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\KmsUnavailableException;
use Heidi\Core\Exception\RuntimeException;
use SensitiveParameter;

/**
 * Seals a shared secret of a user (the secret of a TOTP) for storage and opens it back in memory, under
 * the KMS of the tenant like a signing key (architecture §3.3), in an envelope of its own use: sealed
 * for one tenant, it does not open for another; sealed as a secret, it does not open as a key.
 */
interface SecretSealerInterface
{
    /**
     * @throws KmsUnavailableException when the KMS of the tenant does not answer or refuses to seal
     * @throws RuntimeException when the envelope cannot be made for another reason
     * @return string opaque bytes, safe to store
     */
    public function seal(Tenant $tenant, #[SensitiveParameter] string $secret): string;

    /**
     * @throws KmsUnavailableException when the KMS of the tenant does not answer or refuses to open
     * @throws RuntimeException when the envelope was not sealed by this use for this tenant
     */
    public function unseal(Tenant $tenant, #[SensitiveParameter] string $sealed): string;
}
