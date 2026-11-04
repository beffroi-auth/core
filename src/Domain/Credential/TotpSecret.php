<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Exception\InvalidArgumentException;
use SensitiveParameter;
use function sprintf;
use function strlen;

/**
 * RFC 6238 §3, RFC 4226 §4
 */
final readonly class TotpSecret
{
    public const int LENGTH = 20;

    /**
     * RFC 4226 §4, R6
     */
    private const int MIN_LENGTH = 16;

    public function __construct(
        #[SensitiveParameter]
        public string $bytes,
    ) {
        if (strlen($bytes) < self::MIN_LENGTH) {
            throw new InvalidArgumentException(sprintf('A TOTP secret is at least %d bytes long (RFC 4226 §4).', self::MIN_LENGTH));
        }
    }
}
