<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Encoding;

use function base64_decode;
use function base64_encode;
use function rtrim;
use function strtr;

/**
 * RFC 4648 §5 without padding, as RFC 7515 §2 defines "base64url".
 *
 * **It is in the core because the core writes octets too.** A credential ID of WebAuthn is raw bytes, and
 * every place that hands those bytes to a reader hands them over in this alphabet: the column that holds
 * one (BytesType of the bundle), the document a person downloads (CredentialsChapter), the hash in a
 * token and the thumbprint of a certificate in the module. Two places that must agree are one place, so
 * the encoding is here, below all of them, and nothing writes the three characters of the substitution a
 * second time.
 */
final readonly class Base64Url
{
    public static function encode(string $octets): string
    {
        return rtrim(strtr(base64_encode($octets), '+/', '-_'), '=');
    }

    /**
     * The octets back, or null when the text is not base64url: what a store reads out of a column it did
     * not write is a text somebody else may have written.
     */
    public static function decode(string $encoded): ?string
    {
        $octets = base64_decode(strtr($encoded, '-_', '+/'), true);

        return $octets === false ? null : $octets;
    }
}
