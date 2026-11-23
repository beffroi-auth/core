<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Encoding;

use function array_key_exists;

/**
 * RFC 7515 §4.1.11, RFC 7516 §4.1.13, RFC 7797 §6
 *
 * The provider understands no extension of JOSE. "b64" counts as one even where "crit" does not name it, because
 * jwt-library reads an unencoded payload all the same.
 */
final readonly class JoseExtension
{
    /**
     * @param array<mixed> $protectedHeader
     */
    public static function isNamedIn(array $protectedHeader): bool
    {
        return array_key_exists('crit', $protectedHeader) || array_key_exists('b64', $protectedHeader);
    }
}
