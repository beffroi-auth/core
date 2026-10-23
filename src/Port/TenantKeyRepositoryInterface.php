<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\KeyAlgorithmInterface;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\RuntimeException;

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
     * Staged, active and retiring keys, in creation order: what the tenant still holds, the keys it keeps
     * included. What it publishes is narrower (PublicKeySet::published(), decision 0020), and a lifecycle
     * step walks this list rather than that one: a key the tenant never publishes rotates like the others.
     *
     * @return list<TenantKey>
     */
    public function findInService(Tenant $tenant): array;

    /**
     * Every key the tenant ever had, the retired ones included, in creation order.
     *
     * @return list<TenantKey>
     */
    public function findAll(Tenant $tenant): array;

    /**
     * A key written, or written again as its life goes on. One material is the key of one algorithm: the "kid" is the
     * thumbprint of the public half (RFC 7638), so a key of another algorithm under a "kid" the store holds is the same
     * material, refused rather than written over it (beffroi/saml S-64).
     *
     * @throws RuntimeException when the store holds that "kid" under another algorithm, or cannot write
     */
    public function save(TenantKey $key): void;
}
