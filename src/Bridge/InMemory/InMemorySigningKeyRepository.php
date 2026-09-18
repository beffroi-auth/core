<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\InMemory;

use Heidi\Core\Domain\Key\SignatureAlgorithm;
use Heidi\Core\Domain\Key\SigningKey;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Port\SigningKeyRepositoryInterface;
use function usort;

final class InMemorySigningKeyRepository implements SigningKeyRepositoryInterface
{
    /**
     * @var array<string, SigningKey> by tenant id and kid
     */
    private array $keys = [];

    /**
     * How many times save() was called, for tests that must see a write happen on an object they already hold.
     */
    public private(set) int $saves = 0;

    public function find(Tenant $tenant, string $kid): ?SigningKey
    {
        return $this->keys[self::index($tenant, $kid)] ?? null;
    }

    public function findActive(Tenant $tenant, SignatureAlgorithm $algorithm): ?SigningKey
    {
        foreach ($this->keys as $key) {
            if ($key->tenant->equals($tenant) && $key->algorithm === $algorithm && $key->isActive()) {
                return $key;
            }
        }

        return null;
    }

    public function findPublished(Tenant $tenant): array
    {
        $published = [];
        foreach ($this->keys as $key) {
            if ($key->tenant->equals($tenant) && $key->isPublished()) {
                $published[] = $key;
            }
        }
        usort($published, static fn (SigningKey $a, SigningKey $b): int => [$a->createdAt, $a->kid] <=> [$b->createdAt, $b->kid]);

        return $published;
    }

    public function save(SigningKey $key): void
    {
        ++$this->saves;
        $this->keys[self::index($key->tenant, $key->kid)] = $key;
    }

    private static function index(Tenant $tenant, string $kid): string
    {
        return $tenant->id->toRfc4122() . '/' . $kid;
    }
}
