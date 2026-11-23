<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

use Beffroi\Core\Domain\Encoding\Base64Url;
use function decbin;
use function ltrim;
use function ord;
use function strlen;

/**
 * RFC 7518 §3.3, §3.5, §4.3, §6.3.1.1
 */
final readonly class RsaModulus
{
    public const int MINIMUM_BITS = 2048;

    /**
     * Leading zero octets are dropped, which some libraries prepend to the modulus.
     *
     * @return int|null null when "n" is not base64url
     */
    public static function bitsOf(string $n): ?int
    {
        $octets = Base64Url::decode($n);
        if ($octets === null) {
            return null;
        }
        $octets = ltrim($octets, "\0");
        if ($octets === '') {
            return 0;
        }

        return ((strlen($octets) - 1) * 8) + strlen(decbin(ord($octets[0])));
    }
}
