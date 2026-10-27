<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Exception\InvalidArgumentException;
use function preg_match;
use SensitiveParameter;
use function sha1;
use function sprintf;
use function strtoupper;
use function substr;

/**
 * The SHA-1 of a password, which is what a list of compromised passwords is searched by, and the one
 * shape of a password that may leave this process.
 *
 * **SHA-1 is not a choice here**, it is the index of the list: the range endpoint of Pwned Passwords is
 * searched by the first five hexadecimal characters of the SHA-1 of the password and answers the suffixes
 * it holds under them. Nothing of this provider stores a password this way and nothing verifies one with
 * it; a password at rest is a typed hash (decision 0002, PasswordHash).
 *
 * **Only the prefix leaves.** Five characters of forty go out, the thirty-five that are left are compared
 * here, and what the list learns is one bucket of about a million passwords and never which of them was
 * asked about.
 */
final readonly class PasswordDigest
{
    /**
     * What goes out, and what the endpoint is addressed by: five hexadecimal characters of the forty.
     */
    public const int PREFIX_LENGTH = 5;

    public const int LENGTH = 40;

    /**
     * @param string $value the forty hexadecimal characters of a SHA-1, upper case
     *
     * @throws InvalidArgumentException when it is not one
     */
    public function __construct(
        #[SensitiveParameter]
        public string $value,
    ) {
        if (preg_match('/^[0-9A-F]{' . self::LENGTH . '}$/D', $value) !== 1) {
            throw new InvalidArgumentException(sprintf('A password digest is %d hexadecimal characters in upper case.', self::LENGTH));
        }
    }

    /**
     * Of a password as it was typed, which is the only place one is read.
     */
    public static function of(#[SensitiveParameter] string $password): self
    {
        return new self(strtoupper(sha1($password)));
    }

    /**
     * The bucket the list is asked for.
     */
    public function prefix(): string
    {
        return substr($this->value, 0, self::PREFIX_LENGTH);
    }

    /**
     * What is compared here, against every suffix the bucket answered.
     */
    public function suffix(): string
    {
        return substr($this->value, self::PREFIX_LENGTH);
    }
}
