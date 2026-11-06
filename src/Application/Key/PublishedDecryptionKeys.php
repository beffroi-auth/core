<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Key;

use Beffroi\Core\Domain\Key\KeyUse;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\DecryptionKeyProviderInterface;
use Beffroi\Core\Port\OpenableKeysInterface;
use Beffroi\Core\Port\PublicKeySetProviderInterface;

/**
 * Each published key a client encrypts to: staged, active and retiring alike, a client choosing among them.
 */
final readonly class PublishedDecryptionKeys implements OpenableKeysInterface
{
    public function __construct(
        private PublicKeySetProviderInterface $published,
        private DecryptionKeyProviderInterface $keys,
    ) {
    }

    public function open(Tenant $tenant): void
    {
        foreach ($this->published->publicKeys($tenant)->forUse(KeyUse::Encryption)->keys as $key) {
            $this->keys->decryptionKey($tenant, $key->kid);
        }
    }
}
