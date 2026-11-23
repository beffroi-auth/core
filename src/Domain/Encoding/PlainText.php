<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Encoding;

use function mb_check_encoding;
use function mb_strlen;
use function preg_match;
use function str_replace;

/**
 * What a person, a client, an upstream provider or an administrator writes is signed into tokens, shown by pages and
 * printed by the console: nothing in it breaks, reorders or hides a part of the line it is shown on.
 */
final readonly class PlainText
{
    /**
     * In characters: a name, a nickname, a locale, a label.
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
     * In characters: a postal address written on several lines.
     */
    public const int MAX_LINES_LENGTH = 1024;

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

    /**
     * OIDC Core §5.1.1: lines separated by a line feed, or by a carriage return and a line feed, and nothing else that
     * a line of text may not hold.
     */
    public static function holdsLines(string $value, int $maxLength): bool
    {
        return self::holds(str_replace(["\r\n", "\n"], ['  ', ' '], $value), $maxLength);
    }
}
