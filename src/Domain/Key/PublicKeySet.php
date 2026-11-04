<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

use function array_map;
use Beffroi\Core\Exception\InvalidArgumentException;
use function sprintf;

final readonly class PublicKeySet
{
    /**
     * @var list<TenantKey>
     */
    public array $keys;

    /**
     * @param iterable<TenantKey> $keys
     *
     * @throws InvalidArgumentException when a key among them is not published
     */
    public function __construct(iterable $keys)
    {
        $published = [];
        foreach ($keys as $key) {
            if (! $key->isPublished()) {
                throw new InvalidArgumentException(sprintf(
                    $key->isInService()
                        ? 'The key "%s" is one the tenant keeps and never publishes; take the published ones with PublicKeySet::published().'
                        : 'The key "%s" has retired and is not published; take the published ones with PublicKeySet::published().',
                    $key->kid,
                ));
            }
            $published[] = $key;
        }
        $this->keys = $published;
    }

    /**
     * @param iterable<TenantKey> $keys
     */
    public static function published(iterable $keys): self
    {
        $published = [];
        foreach ($keys as $key) {
            if ($key->isPublished()) {
                $published[] = $key;
            }
        }

        return new self($published);
    }

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
