<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Key;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Port\OpenableKeysInterface;
use Beffroi\Core\Port\SignatureAlgorithmsInterface;
use Beffroi\Core\Port\SigningKeyProviderInterface;

/**
 * The active key of each wired signature algorithm.
 */
final readonly class ActiveSigningKeys implements OpenableKeysInterface
{
    public function __construct(
        private SignatureAlgorithmsInterface $algorithms,
        private SigningKeyProviderInterface $keys,
    ) {
    }

    public function open(Tenant $tenant): void
    {
        foreach ($this->algorithms->all() as $algorithm) {
            try {
                $this->keys->activeKey($tenant, $algorithm);
            } catch (KeyNotFoundException) {
                // An algorithm with no active key yet has nothing to open, and the others still do.
            }
        }
    }
}
