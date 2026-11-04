<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Key\KeyAlgorithmInterface;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\RuntimeException;
use Beffroi\Core\Port\TenantKeyRepositoryInterface;
use function sprintf;
use function usort;

final class InMemoryTenantKeyRepository implements TenantKeyRepositoryInterface
{
    /**
     * @var array<string, TenantKey> by tenant id and kid
     */
    private array $keys = [];

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

    public function findInService(Tenant $tenant): array
    {
        $inService = [];
        foreach ($this->findAll($tenant) as $key) {
            if ($key->isInService()) {
                $inService[] = $key;
            }
        }

        return $inService;
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
        $held = $this->keys[self::index($key->tenant, $key->kid)] ?? null;
        if ($held !== null && $held->algorithm->name() !== $key->algorithm->name()) {
            throw new RuntimeException(sprintf(
                'The key "%s" of tenant "%s" is held already, and not as a key of %s: one material is the key of one algorithm.',
                $key->kid,
                $key->tenant->slug,
                $key->algorithm->name(),
            ));
        }
        ++$this->saves;
        $this->keys[self::index($key->tenant, $key->kid)] = $key;
    }

    private static function index(Tenant $tenant, string $kid): string
    {
        return $tenant->id->toRfc4122() . '/' . $kid;
    }
}
