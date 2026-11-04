<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Encoding;

use function base64_decode;
use function base64_encode;
use function preg_match;
use function rtrim;
use function strtr;

/**
 * RFC 4648 §5, RFC 7515 §2
 */
final readonly class Base64Url
{
    private const string PATTERN = '/^[A-Za-z0-9_-]*$/D';

    public static function encode(string $octets): string
    {
        return rtrim(strtr(base64_encode($octets), '+/', '-_'), '=');
    }

    /**
     * The alphabet is checked before the substitution, or a standard base64 text would decode as base64url.
     */
    public static function decode(string $encoded): ?string
    {
        if (preg_match(self::PATTERN, $encoded) !== 1) {
            return null;
        }
        $octets = base64_decode(strtr($encoded, '-_', '+/'), true);

        return $octets === false ? null : $octets;
    }
}
