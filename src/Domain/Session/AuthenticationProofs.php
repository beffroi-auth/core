<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Session;

use function array_keys;
use function array_map;
use DateTimeImmutable;
use Heidi\Core\Exception\InvalidArgumentException;
use function max;

/**
 * Which authentication methods were proven for a session, and when each was proven last. A password
 * re-entered five minutes ago and a second factor from the login two hours ago are two entries, not one
 * timestamp: a policy on freshness or strength needs both (symfony/symfony#66064 makes the same choice for
 * the security token, and the bundle copies this map into it).
 *
 * The "amr" claim is the list of keys; "auth_time" is the most recent proof.
 */
final readonly class AuthenticationProofs
{
    /**
     * @var non-empty-array<string, DateTimeImmutable> method reference => moment it was last proven
     */
    public array $entries;

    /**
     * @param array<string, DateTimeImmutable> $entries
     */
    public function __construct(array $entries)
    {
        if ($entries === []) {
            throw new InvalidArgumentException('A session holds at least one authentication proof (RFC 8176).');
        }
        $validated = [];
        foreach ($entries as $method => $provenAt) {
            $validated[(string) new AuthenticationMethod((string) $method)] = $provenAt;
        }
        $this->entries = $validated;
    }

    public static function of(AuthenticationMethod $method, DateTimeImmutable $provenAt): self
    {
        return new self([
            $method->value => $provenAt,
        ]);
    }

    /**
     * Records a proof. The newest proof of a method replaces the older one; other methods keep their own date.
     */
    public function with(AuthenticationMethod $method, DateTimeImmutable $provenAt): self
    {
        $entries = $this->entries;
        $current = $entries[$method->value] ?? null;
        if ($current === null || $provenAt > $current) {
            $entries[$method->value] = $provenAt;
        }

        return new self($entries);
    }

    public function has(AuthenticationMethod $method): bool
    {
        return isset($this->entries[$method->value]);
    }

    public function provenAt(AuthenticationMethod $method): ?DateTimeImmutable
    {
        return $this->entries[$method->value] ?? null;
    }

    /**
     * The most recent proof: what OIDC Core §2 calls auth_time once a re-authentication has happened.
     */
    public function latest(): DateTimeImmutable
    {
        return max($this->entries);
    }

    /**
     * @return non-empty-list<AuthenticationMethod>
     */
    public function methods(): array
    {
        return array_map(static fn (string $value): AuthenticationMethod => new AuthenticationMethod($value), array_keys($this->entries));
    }

    /**
     * @return non-empty-list<string> the "amr" claim values
     */
    public function methodReferences(): array
    {
        return array_keys($this->entries);
    }

    /**
     * @return non-empty-array<string, int> the shape of Symfony's TokenInterface::getAuthenticationProofs()
     */
    public function toTimestamps(): array
    {
        return array_map(static fn (DateTimeImmutable $provenAt): int => $provenAt->getTimestamp(), $this->entries);
    }
}
