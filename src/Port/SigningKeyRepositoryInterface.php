<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Key\SignatureAlgorithm;
use Heidi\Core\Domain\Key\SigningKey;
use Heidi\Core\Domain\Tenant\Tenant;

interface SigningKeyRepositoryInterface
{
    public function find(Tenant $tenant, string $kid): ?SigningKey;

    public function findActive(Tenant $tenant, SignatureAlgorithm $algorithm): ?SigningKey;

    /**
     * Staged, active and retiring keys, in creation order.
     *
     * @return list<SigningKey>
     */
    public function findPublished(Tenant $tenant): array;

    public function save(SigningKey $key): void;
}
