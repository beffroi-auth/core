<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Exception\InvalidArgumentException;
use SensitiveParameter;
use function sprintf;
use function strlen;

/**
 * The shared secret of a TOTP (RFC 6238 §3, RFC 4226 §4), in clear, in memory only: what the enrolment
 * shows the user once and what a code is verified against. Stored sealed by the KMS of the tenant
 * (SecretSealerInterface), never as is. 160 bits, the length RFC 4226 §4 recommends for HMAC-SHA-1.
 */
final readonly class TotpSecret
{
    public const int LENGTH = 20;

    /**
     * RFC 4226 §4, R6: the shared secret is at least 128 bits long.
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
