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

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * What follows the last "@", without regard to ASCII case, which is how a domain name is compared (RFC
     * 4343 §3): what an upstream provider answers for is a domain, and `Acme.Example` is the domain it
     * declared as `acme.example`. The address is valid, so there is one "@" at least.
     */
    public function domain(): string
    {
        return strtolower(substr($this->value, (int) strrpos($this->value, '@') + 1));
    }

    /**
     * The same mailbox, as this provider counts one: without regard to case, the local part included (issue
     * #781). RFC 5321 §2.4 leaves the case of a local part to the receiving server, and every server a person
     * reads their mail on ignores it, so `Bob@acme.example` and `bob@acme.example` are one person's mail and
     * never two persons'. It is what the projection the blind index of an address is derived under keeps of
     * an address, so that an adapter in memory finds what the storage finds.
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
