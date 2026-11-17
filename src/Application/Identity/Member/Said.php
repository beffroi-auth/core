<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity\Member;

use Beffroi\Core\Exception\InvalidArgumentException;
use function is_bool;
use function is_string;
use function sprintf;
use function trim;

/**
 * A value of the document of a person, as an administrator wrote it: a text trimmed, an empty one saying nothing, as
 * `beffroi:user:profile` reads its options; a yes or a no only as a boolean.
 */
final class Said
{
    /**
     * @throws InvalidArgumentException when the value is neither a string nor null
     */
    public static function text(mixed $value, string $name): ?string
    {
        if ($value === null) {
            return null;
        }
        if (! is_string($value)) {
            throw new InvalidArgumentException(sprintf('"%s" is a string, or null to take it away.', $name));
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @throws InvalidArgumentException when the value is not a boolean
     */
    public static function flag(mixed $value, string $name): bool
    {
        if (! is_bool($value)) {
            throw new InvalidArgumentException(sprintf('"%s" is true or false.', $name));
        }

        return $value;
    }

    /**
     * How the history of a person writes a yes or a no, null when there is nothing to have verified.
     */
    public static function written(?bool $flag): ?string
    {
        return match ($flag) {
            null => null,
            true => 'true',
            false => 'false',
        };
    }
}
