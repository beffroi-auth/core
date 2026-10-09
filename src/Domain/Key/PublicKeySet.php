<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

use function array_map;
use Beffroi\Core\Exception\InvalidArgumentException;
use function sprintf;

/**
 * The published keys of a tenant, whatever they are published for: the JWK Set of RFC 7517 §5 carries the
 * two uses side by side, each JWK saying which it is (§4.2). A consumer asks for the use it needs and sees
 * nothing else: what verifies a signature never looks at a key published to be encrypted to, and the other
 * way round.
 *
 * Published is narrower than in service: a key the tenant keeps is not in this set whatever its status
 * (TenantKey::isPublished(), decision 0020), because the set is what the tenant answers to whoever reads
 * its issuer and nothing there is addressed to a reader of it.
 */
final readonly class PublicKeySet
{
    /**
     * @var list<TenantKey>
     */
    public array $keys;

    /**
     * @param iterable<TenantKey> $keys every one of them published
     *
     * @throws InvalidArgumentException when a key that has retired, or one the tenant keeps, is among
     *                                  them: what a tenant publishes is not something to be guessed from
     *                                  a longer list
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
     * The published ones among whatever is given, the retired ones and the ones the tenant keeps left out.
     * Named, because a set that quietly drops what it was handed is a set nobody can be sure of: a caller
     * that means "all of them" says so here, and one that means "these" uses the constructor and hears
     * about a key that is not published.
     *
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
