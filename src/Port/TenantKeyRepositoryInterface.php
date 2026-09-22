<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Key\KeyAlgorithmInterface;
use Heidi\Core\Domain\Key\TenantKey;
use Heidi\Core\Domain\Tenant\Tenant;

/**
 * The keys of a tenant, whatever they are published for: one store, one lifecycle, one set of commands.
 */
interface TenantKeyRepositoryInterface
{
    public function find(Tenant $tenant, string $kid): ?TenantKey;

    /**
     * The active key of that algorithm, which names the use it is active for: a tenant has one active key
     * per algorithm, so one that signs and one that is encrypted to live side by side.
     */
    public function findActive(Tenant $tenant, KeyAlgorithmInterface $algorithm): ?TenantKey;

    /**
     * Staged, active and retiring keys, in creation order.
     *
     * @return list<TenantKey>
     */
    public function findPublished(Tenant $tenant): array;

    /**
     * Every key the tenant ever had, the retired ones included, in creation order.
     *
     * @return list<TenantKey>
     */
    public function findAll(Tenant $tenant): array;

    public function save(TenantKey $key): void;
}
