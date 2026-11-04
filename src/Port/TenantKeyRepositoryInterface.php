<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\KeyAlgorithmInterface;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\RuntimeException;

interface TenantKeyRepositoryInterface
{
    public function find(Tenant $tenant, string $kid): ?TenantKey;

    public function findActive(Tenant $tenant, KeyAlgorithmInterface $algorithm): ?TenantKey;

    /**
     * The keys the tenant keeps included: a key it never publishes rotates like the others.
     *
     * @return list<TenantKey> in creation order
     */
    public function findInService(Tenant $tenant): array;

    /**
     * @return list<TenantKey> in creation order
     */
    public function findAll(Tenant $tenant): array;

    /**
     * @throws RuntimeException when the store holds that "kid" under another algorithm, or cannot write
     */
    public function save(TenantKey $key): void;
}
