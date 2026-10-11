<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Domain\Encoding\Base64Url;
use function hash_equals;
use Beffroi\Core\Exception\InvalidArgumentException;
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

    /**
     * The sixty-four octets as a column holds them, base64url without padding (RFC 4648 §5): the alphabet
     * of the core, which is also the one the document a person downloads writes those same bytes in.
     */
    public function toDocument(): string
    {
        return Base64Url::encode($this->value);
    }

    /**
     * @throws InvalidArgumentException when the text is not base64url, or is not sixty-four octets
     */
    public static function fromDocument(string $document): self
    {
        return new self(Base64Url::decode($document) ?? throw new InvalidArgumentException('A user handle is base64url.'));
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }
}
