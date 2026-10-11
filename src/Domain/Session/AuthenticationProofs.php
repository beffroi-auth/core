<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use function array_keys;
use function array_map;
use function count;
use DateTimeImmutable;
use Beffroi\Core\Exception\InvalidArgumentException;
use function is_int;
use function is_string;
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
        return AuthenticationMethod::factorsOf($this->methods());
    }

    /**
     * The "amr" claim values (RFC 8176 §2): the methods, and "mfa" when two factors of different kinds
     * were proven, since §2 lets the specific methods stand next to it.
     *
     * @return non-empty-list<string>
     */
    public function methodReferences(): array
    {
        $references = array_keys($this->entries);
        if (count($this->factors()) >= 2 && ! isset($this->entries[AuthenticationMethod::MULTIPLE_FACTORS])) {
            $references[] = AuthenticationMethod::MULTIPLE_FACTORS;
        }

        return $references;
    }

    /**
     * @return non-empty-array<string, int> the shape of Symfony's TokenInterface::getAuthenticationProofs()
     */
    public function toTimestamps(): array
    {
        return array_map(static fn (DateTimeImmutable $provenAt): int => $provenAt->getTimestamp(), $this->entries);
    }

    /**
     * The proofs back, out of the document a column holds them as, which is the shape above: a method
     * reference on the Unix timestamp it was last proven at, and nothing else.
     *
     * @param array<array-key, mixed> $timestamps
     *
     * @throws InvalidArgumentException when an entry is not a method reference on a Unix timestamp, or
     *                                  when there is no entry at all
     */
    public static function fromTimestamps(array $timestamps): self
    {
        $entries = [];
        foreach ($timestamps as $method => $timestamp) {
            if (! is_string($method) || ! is_int($timestamp)) {
                throw new InvalidArgumentException('Authentication proofs map a method reference to a Unix timestamp.');
            }
            $entries[$method] = new DateTimeImmutable('@' . $timestamp);
        }

        return new self($entries);
    }
}
