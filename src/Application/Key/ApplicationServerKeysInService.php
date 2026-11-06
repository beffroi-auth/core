<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Key;

use Beffroi\Core\Domain\Key\ApplicationServerAlgorithmInterface;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\ApplicationServerKeyProviderInterface;
use Beffroi\Core\Port\OpenableKeysInterface;
use Beffroi\Core\Port\TenantKeyRepositoryInterface;

/**
 * Each application server key in service: a subscription is pushed under the key it was taken under.
 */
final readonly class ApplicationServerKeysInService implements OpenableKeysInterface
{
    public function __construct(
        private TenantKeyRepositoryInterface $repository,
        private ApplicationServerKeyProviderInterface $keys,
    ) {
    }

    public function open(Tenant $tenant): void
    {
        foreach ($this->repository->findInService($tenant) as $key) {
            if ($key->algorithm instanceof ApplicationServerAlgorithmInterface) {
                $this->keys->applicationServerKey($tenant, $key->kid);
            }
        }
    }
}
