<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use Beffroi\Core\Exception\InvalidArgumentException;
use function preg_match;
use function sprintf;
use Stringable;
use function strlen;

/**
 * The "sub" claim (OIDC Core §2): at most 255 ASCII characters, never changed, never reassigned, distinct
 * from the technical id so that an import keeps it.
 */
final readonly class Subject implements Stringable
{
    public function __construct(
        public string $value,
    ) {
        if ($value === '' || strlen($value) > 255) {
            throw new InvalidArgumentException('A subject is 1 to 255 characters long.');
        }
        if (preg_match('/^[\x21-\x7E]+$/D', $value) !== 1) {
            throw new InvalidArgumentException(sprintf('The subject "%s" must be made of printable ASCII characters without spaces.', $value));
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
