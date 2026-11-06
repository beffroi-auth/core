<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Key;

use Beffroi\Core\Domain\Key\KeptSigningAlgorithmInterface;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Port\KeptSigningKeyProviderInterface;
use Beffroi\Core\Port\OpenableKeysInterface;

/**
 * The active key of each wired algorithm that signs outside the JWK Set.
 */
final readonly class ActiveKeptSigningKeys implements OpenableKeysInterface
{
    /**
     * @param iterable<KeptSigningAlgorithmInterface> $algorithms
     */
    public function __construct(
        private iterable $algorithms,
        private KeptSigningKeyProviderInterface $keys,
    ) {
    }

    public function open(Tenant $tenant): void
    {
        foreach ($this->algorithms as $algorithm) {
            try {
                $this->keys->activeKeptSigningKey($tenant, $algorithm);
            } catch (KeyNotFoundException) {
                // An algorithm with no active key yet has nothing to open, and the others still do.
            }
        }
    }
}
