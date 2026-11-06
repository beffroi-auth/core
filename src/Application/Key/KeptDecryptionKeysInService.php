<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Key;

use Beffroi\Core\Domain\Key\KeptEncryptionAlgorithmInterface;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\KeptDecryptionKeyProviderInterface;
use Beffroi\Core\Port\OpenableKeysInterface;
use Beffroi\Core\Port\TenantKeyRepositoryInterface;

/**
 * The keys in service each holder outside the JWK Set encrypts to, opened algorithm by algorithm.
 */
final readonly class KeptDecryptionKeysInService implements OpenableKeysInterface
{
    public function __construct(
        private TenantKeyRepositoryInterface $repository,
        private KeptDecryptionKeyProviderInterface $keys,
    ) {
    }

    public function open(Tenant $tenant): void
    {
        $algorithms = [];
        $kids = [];
        foreach ($this->repository->findInService($tenant) as $key) {
            if ($key->algorithm instanceof KeptEncryptionAlgorithmInterface) {
                $algorithms[$key->algorithm->name()] = $key->algorithm;
                $kids[$key->algorithm->name()][] = $key->kid;
            }
        }
        foreach ($algorithms as $name => $algorithm) {
            $this->keys->keptDecryptionKeys($tenant, $algorithm, $kids[$name]);
        }
    }
}
