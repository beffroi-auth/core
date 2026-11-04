<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Encoding;

use function preg_match;

/**
 * RFC 3986 §3.2.2, RFC 1123 §2.1
 *
 * Narrower than the reg-name: a ";" or a "," would end a directive or a policy of a Content-Security-Policy.
 */
final readonly class Host
{
    /**
     * Without anchors: other patterns embed it.
     */
    public const string PATTERN = '(?:[A-Za-z0-9.-]+|\[[0-9A-Fa-f:.]+\])';

    public static function isHost(string $host): bool
    {
        return preg_match('~^' . self::PATTERN . '\z~', $host) === 1;
    }
}
