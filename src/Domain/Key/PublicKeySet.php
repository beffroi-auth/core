<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Key;

use function array_map;

/**
 * The published keys of a tenant (staged, active, retiring), whatever they are published for: the JWK Set
 * of RFC 7517 §5 carries the two uses side by side, each JWK saying which it is (§4.2). A consumer asks
 * for the use it needs and sees nothing else: what verifies a signature never looks at a key published to
 * be encrypted to, and the other way round.
 */
final readonly class PublicKeySet
{
    /**
     * @var list<TenantKey>
     */
    public array $keys;

    /**
     * @param iterable<TenantKey> $keys
     */
    public function __construct(iterable $keys)
    {
        $published = [];
        foreach ($keys as $key) {
            if ($key->isPublished()) {
                $published[] = $key;
            }
        }
        $this->keys = $published;
    }

    /**
     * The same set narrowed to one use, which is what a consumer of keys works with.
     */
    public function forUse(KeyUse $use): self
    {
        $kept = [];
        foreach ($this->keys as $key) {
            if ($key->algorithm->keyUse() === $use) {
                $kept[] = $key;
            }
        }

        return new self($kept);
    }

    public function find(string $kid): ?TenantKey
    {
        foreach ($this->keys as $key) {
            if ($key->kid === $kid) {
                return $key;
            }
        }

        return null;
    }

    public function isEmpty(): bool
    {
        return $this->keys === [];
    }

    /**
     * @return array{keys: list<array<string, string>>}
     */
    public function toJwks(): array
    {
        return [
            'keys' => array_map(static fn (TenantKey $key): array => $key->publicJwk, $this->keys),
        ];
    }
}
