<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use function mb_check_encoding;
use function mb_strlen;
use function preg_match;

/**
 * What a person says of themselves is signed into the tokens of every relying party and shown by their pages: nothing
 * in it breaks, reorders or hides a part of the line it is shown on.
 */
final readonly class PlainText
{
    /**
     * In characters: a name, a nickname, a locale.
     */
    public const int MAX_LENGTH = 255;

    /**
     * In characters: an address of a web page, and its query.
     */
    public const int MAX_URL_LENGTH = 2048;

    /**
     * In characters: E.164 and an extension of RFC 3966 with their separators (OIDC Core §5.1).
     */
    public const int MAX_PHONE_NUMBER_LENGTH = 64;

    /**
     * The zero-width non-joiner and joiner stay: Persian and the Indic scripts write words with them.
     */
    public static function holds(string $value, int $maxLength): bool
    {
        return (
            mb_check_encoding($value, 'UTF-8')
            && preg_match('/[\p{Cc}\p{Zl}\p{Zp}]|(?![\x{200C}\x{200D}])\p{Cf}/u', $value) === 0
            && mb_strlen($value) <= $maxLength
        );
    }
}
