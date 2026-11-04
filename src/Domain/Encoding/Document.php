<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Encoding;

use function array_is_list;
use Beffroi\Core\Exception\InvalidArgumentException;
use function get_debug_type;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function sprintf;

/**
 * A JSON document a value object was written as; a member of another type is refused, never coerced.
 */
final readonly class Document
{
    /**
     * @param array<array-key, mixed> $members
     */
    public function __construct(
        public array $members = [],
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function of(mixed $value, string $named): self
    {
        if (! is_array($value)) {
            throw new InvalidArgumentException(sprintf('The %s must be a JSON object, got %s.', $named, get_debug_type($value)));
        }

        return new self($value);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function nullableString(string $name): ?string
    {
        $value = $this->members[$name] ?? null;
        if ($value !== null && ! is_string($value)) {
            throw new InvalidArgumentException(sprintf('The member "%s" must be a string or absent.', $name));
        }

        return $value;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function nullableInt(string $name): ?int
    {
        $value = $this->members[$name] ?? null;
        if ($value !== null && ! is_int($value)) {
            throw new InvalidArgumentException(sprintf('The member "%s" must be an integer or absent.', $name));
        }

        return $value;
    }

    /**
     * A JSON boolean only, never a string or a number cast to one.
     *
     * @throws InvalidArgumentException
     */
    public function nullableBool(string $name): ?bool
    {
        $value = $this->members[$name] ?? null;
        if ($value !== null && ! is_bool($value)) {
            throw new InvalidArgumentException(sprintf('The member "%s" must be a boolean or absent.', $name));
        }

        return $value;
    }

    /**
     * A whole number decodes as an integer, which is read as a float.
     *
     * @throws InvalidArgumentException
     */
    public function nullableFloat(string $name): ?float
    {
        $value = $this->members[$name] ?? null;
        if ($value === null) {
            return null;
        }
        if (! is_float($value) && ! is_int($value)) {
            throw new InvalidArgumentException(sprintf('The member "%s" must be a number or absent.', $name));
        }

        return (float) $value;
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return array<array-key, mixed>|null
     */
    public function nested(string $name): ?array
    {
        $value = $this->members[$name] ?? null;

        return $value === null ? null : self::of($value, sprintf('member "%s"', $name))->members;
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return list<mixed>
     */
    public function entries(string $name): array
    {
        $value = $this->members[$name] ?? null;
        if ($value === null) {
            return [];
        }
        if (! is_array($value) || ! array_is_list($value)) {
            throw new InvalidArgumentException(sprintf('The member "%s" must be a JSON list.', $name));
        }

        return $value;
    }
}
