<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Key;

/**
 * The JWS algorithms the provider signs with (RFC 7518 §3). RS256 is mandatory (OIDC Core §15.1); no HMAC (RFC 8725 §3.1).
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
     * RFC 7518 §3.3.
     */
    public function minimumKeyBits(): int
    {
        return match ($this) {
            self::RS256 => 2048,
        };
    }

    /**
     * As hash() names it; "at_hash" uses the same function (OIDC Core §3.1.3.6).
     */
    public function digestAlgorithm(): string
    {
        return match ($this) {
            self::RS256 => 'sha256',
        };
    }
}
