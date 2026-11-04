<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use const FILTER_FLAG_EMAIL_UNICODE;
use const FILTER_VALIDATE_EMAIL;
use function filter_var;
use function mb_strtolower;
use Beffroi\Core\Exception\InvalidArgumentException;
use function sprintf;
use Stringable;
use function strrpos;
use function strtolower;
use function substr;

final readonly class EmailAddress implements Stringable
{
    public function __construct(
        public string $value,
    ) {
        if (filter_var($value, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE) === false) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid email address.', $value));
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function ofAnySpelling(string $address): self
    {
        $domain = EmailDomain::ofAddress($address);

        return new self(substr($address, 0, (int) strrpos($address, '@')) . '@' . $domain->value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * RFC 4343 §3
     *
     * Federation reads the domain through EmailDomain::ofAddress(), never through this.
     */
    public function domain(): string
    {
        return strtolower(substr($this->value, (int) strrpos($this->value, '@') + 1));
    }

    /**
     * The local part too ignores case, as the blind index of an address does; RFC 5321 §2.4 leaves it to the
     * receiving server.
     */
    public function isTheSameMailboxAs(self $other): bool
    {
        return $this->localPart() === $other->localPart() && $this->domain() === $other->domain();
    }

    private function localPart(): string
    {
        return mb_strtolower(substr($this->value, 0, (int) strrpos($this->value, '@')));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
