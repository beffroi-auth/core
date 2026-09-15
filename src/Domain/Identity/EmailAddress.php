<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Identity;

use const FILTER_FLAG_EMAIL_UNICODE;
use const FILTER_VALIDATE_EMAIL;
use function filter_var;
use Heidi\Core\Exception\InvalidArgumentException;
use function sprintf;
use Stringable;

final readonly class EmailAddress implements Stringable
{
    public function __construct(
        public string $value,
    ) {
        if (filter_var($value, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE) === false) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid email address.', $value));
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
