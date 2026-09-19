<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Session;

use function array_keys;
use function array_map;
use DateTimeImmutable;
use Heidi\Core\Exception\InvalidArgumentException;
use function in_array;
use function max;

/**
 * The methods proven for a session and when each was proven last (the shape of symfony/symfony#66064).
 * "amr" is the list of keys, "auth_time" the most recent proof.
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
     * Several methods proven at once, as an assertion of a passkey does (RFC 8176 §2 lists them all).
     *
     * @param non-empty-list<AuthenticationMethod> $methods
     */
    public static function ofAll(array $methods, DateTimeImmutable $provenAt): self
    {
        $entries = [];
        foreach ($methods as $method) {
            $entries[$method->value] = $provenAt;
        }

        return new self($entries);
    }

    /**
     * The newest proof of a method replaces the older one.
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
     * The "auth_time" (OIDC Core §2).
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
     * The distinct kinds of factor, in the order they were proven.
     *
     * @return list<AuthenticationFactor>
     */
    public function factors(): array
    {
        $factors = [];
        foreach ($this->methods() as $method) {
            $factor = $method->factor();
            if ($factor !== null && ! in_array($factor, $factors, true)) {
                $factors[] = $factor;
            }
        }

        return $factors;
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
