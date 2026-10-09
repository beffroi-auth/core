<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

/**
 * The life of a key of a tenant (architecture §3.3, OIDC Core §10.1.1): in service before it is used, in
 * service after it stopped while what it produced, or what was encrypted to it, can still arrive, then out
 * of service.
 *
 * Being in service is not being published: what a tenant publishes is what its JWK Set carries, which is
 * the keys in service of an algorithm it publishes for, and a tenant also holds keys it never publishes
 * (PublishedKeyAlgorithmInterface, decision 0020). The status says the first and nothing of the second.
 */
enum KeyStatus: string
{
    case Staged = 'staged';
    case Active = 'active';
    case Retiring = 'retiring';
    case Retired = 'retired';

    public function isInService(): bool
    {
        return $this !== self::Retired;
    }
}
