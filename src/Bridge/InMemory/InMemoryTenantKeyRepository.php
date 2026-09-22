<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\InMemory;

use Heidi\Core\Domain\Key\KeyAlgorithmInterface;
use Heidi\Core\Domain\Key\TenantKey;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Port\TenantKeyRepositoryInterface;
use function usort;

final class InMemoryTenantKeyRepository implements TenantKeyRepositoryInterface
{
    /**
     * @var array<string, TenantKey> by tenant id and kid
     */
    private array $keys = [];

    /**
     * For tests that must see a write happen on an object they already hold.
     */
    public private(set) int $saves = 0;

    public function find(Tenant $tenant, string $kid): ?TenantKey
    {
        return $this->keys[self::index($tenant, $kid)] ?? null;
    }

    public function findActive(Tenant $tenant, KeyAlgorithmInterface $algorithm): ?TenantKey
    {
        foreach ($this->keys as $key) {
            if ($key->tenant->equals($tenant) && $key->algorithm->name() === $algorithm->name() && $key->isActive()) {
                return $key;
            }
        }

        return null;
    }

    public function findPublished(Tenant $tenant): array
    {
        $published = [];
        foreach ($this->findAll($tenant) as $key) {
            if ($key->isPublished()) {
                $published[] = $key;
            }
        }

        return $published;
    }

    public function findAll(Tenant $tenant): array
    {
        $all = [];
        foreach ($this->keys as $key) {
            if ($key->tenant->equals($tenant)) {
                $all[] = $key;
            }
        }
        usort($all, static fn (TenantKey $a, TenantKey $b): int => [$a->createdAt, $a->kid] <=> [$b->createdAt, $b->kid]);

        return $all;
    }

    public function save(TenantKey $key): void
    {
        ++$this->saves;
        $this->keys[self::index($key->tenant, $key->kid)] = $key;
    }

    private static function index(Tenant $tenant, string $kid): string
    {
        return $tenant->id->toRfc4122() . '/' . $kid;
    }
}
