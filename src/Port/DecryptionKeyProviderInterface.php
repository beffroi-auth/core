<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\UnsealedKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;

/**
 * The key is the one the "kid" names, never one tried among the keys the tenant publishes.
 */
interface DecryptionKeyProviderInterface
{
    /**
     * @throws KeyNotFoundException when the "kid" names no published encryption key
     * @throws KmsUnavailableException
     */
    public function decryptionKey(Tenant $tenant, string $kid): UnsealedKey;
}
