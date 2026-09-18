<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Key;

use function array_map;

/**
 * The published keys of a tenant (staged, active, retiring): the JWK Set (RFC 7517 §5) and what verifies a token.
 */
final readonly class PublicKeySet
{
    /**
     * @var list<SigningKey>
     */
    public array $keys;

    /**
     * @param iterable<SigningKey> $keys
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

    public function find(string $kid): ?SigningKey
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
            'keys' => array_map(static fn (SigningKey $key): array => $key->publicJwk, $this->keys),
        ];
    }
}
