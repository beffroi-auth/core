<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Credential;

use function hash_equals;
use Heidi\Core\Exception\InvalidArgumentException;
use function random_bytes;
use function sprintf;
use function strlen;

/**
 * The "user.id" of WebAuthn Level 3 §5.4.3, what an authenticator stores with a passkey and hands back
 * with an assertion: 64 random bytes drawn once per user, never the "sub" nor the e-mail (§14.6.1, a user
 * handle carries no personal information), and shared by every passkey of the user.
 */
final readonly class PasskeyUserHandle
{
    public const int LENGTH = 64;

    public function __construct(
        public string $value,
    ) {
        if (strlen($value) !== self::LENGTH) {
            throw new InvalidArgumentException(sprintf('A passkey user handle is %d bytes long.', self::LENGTH));
        }
    }

    public static function generate(): self
    {
        return new self(random_bytes(self::LENGTH));
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }
}
