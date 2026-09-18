<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Key;

use function array_map;

/**
 * The published keys of a tenant, staged, active and retiring: what the JWK Set endpoint serves (RFC 7517
 * §5) and what verifies a token the tenant issued. A staged key is in the set so that a relying party learns
 * it before it signs; a retired one is out, and a token naming it is refused.
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
