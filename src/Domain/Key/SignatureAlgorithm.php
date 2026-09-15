<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Key;

/**
 * The JWS algorithms the provider signs with (RFC 7518 §3). RS256 is what OIDC Core §15.1 requires every
 * provider to support and what Symfony's oidc_login expects by default. No HMAC: a symmetric key shared with
 * every client is not a signature (RFC 8725 §3.1).
 */
enum SignatureAlgorithm: string
{
    case RS256 = 'RS256';

    public function keyType(): string
    {
        return match ($this) {
            self::RS256 => 'RSA',
        };
    }

    /**
     * RFC 7518 §3.3: a key of 2048 bits or larger MUST be used with RS256.
     */
    public function minimumKeyBits(): int
    {
        return match ($this) {
            self::RS256 => 2048,
        };
    }
}
