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
 * The document a value object was written as, read member by member: every reading says what it expected,
 * and a member that is not that is refused instead of coerced.
 *
 * **It is in the core because the core writes documents.** A value object a column holds whole writes one
 * (`Address::toDocument()`), and the object that reads it back is the object that wrote it, so the reading
 * of a member is here, below all of them, exactly as Base64Url is: nothing writes
 * `is_string($data['x'] ?? null)` a second time, and a member reads the same way in every one of them.
 *
 * Nothing of a store is here. Where a document was read out of, and what becomes of a refusal, belong to
 * whoever holds it: a DBAL type of the application turns the exception of this package into the
 * `ValueNotConvertible` of Doctrine, naming the column it was reading.
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
     * What a member that is itself a document reads as, and what a list of documents holds: a value that
     * is not one is refused where it was found, under the name that led to it.
     *
     * @throws InvalidArgumentException when the value is not a JSON object
     */
    public static function of(mixed $value, string $named): self
    {
        if (! is_array($value)) {
            throw new InvalidArgumentException(sprintf('The %s must be a JSON object, got %s.', $named, get_debug_type($value)));
        }

        return new self($value);
    }

    /**
     * @throws InvalidArgumentException when the member is there and is not a string
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
     * @throws InvalidArgumentException when the member is there and is not an integer
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
     * A JSON boolean, never a string or a number read as one.
     *
     * @throws InvalidArgumentException when the member is there and is not a boolean
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
     * A JSON number, which a whole one decodes as an integer: a latitude written "48" is the latitude 48.0.
     *
     * @throws InvalidArgumentException when the member is there and is not a number
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
     * The members of a member that is itself a document, for the object that document belongs to to read.
     *
     * @throws InvalidArgumentException when the member is there and is not a JSON object
     *
     * @return array<array-key, mixed>|null
     */
    public function nested(string $name): ?array
    {
        $value = $this->members[$name] ?? null;

        return $value === null ? null : self::of($value, sprintf('member "%s"', $name))->members;
    }

    /**
     * The values of a member that is a JSON list, in their order, and none when the member is absent: what
     * each of them is belongs to whoever reads it.
     *
     * @throws InvalidArgumentException when the member is there and is not a JSON list
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
