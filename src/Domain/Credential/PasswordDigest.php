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
 * SHA-1 because the Pwned Passwords range endpoint is indexed by it; only the prefix ever leaves.
 */
final readonly class PasswordDigest
{
    public const int PREFIX_LENGTH = 5;

    public const int LENGTH = 40;

    /**
     * @param string $value a SHA-1 in upper-case hexadecimal
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        #[SensitiveParameter]
        public string $value,
    ) {
        if (preg_match('/^[0-9A-F]{' . self::LENGTH . '}$/D', $value) !== 1) {
            throw new InvalidArgumentException(sprintf('A password digest is %d hexadecimal characters in upper case.', self::LENGTH));
        }
    }

    public static function of(#[SensitiveParameter] string $password): self
    {
        return new self(strtoupper(sha1($password)));
    }

    public function prefix(): string
    {
        return substr($this->value, 0, self::PREFIX_LENGTH);
    }

    public function suffix(): string
    {
        return substr($this->value, self::PREFIX_LENGTH);
    }
}
