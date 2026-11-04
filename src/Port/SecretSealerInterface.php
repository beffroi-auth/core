<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KmsUnavailableException;
use Beffroi\Core\Exception\RuntimeException;
use SensitiveParameter;

/**
 * Sealed for one tenant, a secret does not open for another; sealed as a secret, it does not open as a key.
 */
interface SecretSealerInterface
{
    /**
     * @throws KmsUnavailableException
     * @throws RuntimeException
     */
    public function seal(Tenant $tenant, #[SensitiveParameter] string $secret): string;

    /**
     * @throws KmsUnavailableException
     * @throws RuntimeException when the envelope was not sealed by this use for this tenant
     */
    public function unseal(Tenant $tenant, #[SensitiveParameter] string $sealed): string;
}
