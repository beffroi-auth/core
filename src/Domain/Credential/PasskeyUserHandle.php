<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Domain\Encoding\Base64Url;
use function hash_equals;
use Beffroi\Core\Exception\InvalidArgumentException;
use function sprintf;
use function strlen;

/**
 * WebAuthn Level 3 §5.4.3, §14.6.1
 *
 * Random bytes, never the "sub" nor the e-mail: a user handle carries no personal information.
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

    public function toDocument(): string
    {
        return Base64Url::encode($this->value);
    }

    /**
     * @throws InvalidArgumentException
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
