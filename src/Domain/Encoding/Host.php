<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Encoding;

use function preg_match;

/**
 * The host of a URL this provider hands a browser or names in a Content-Security-Policy (RFC 3986 §3.2.2): a name for
 * the DNS, letters, digits, hyphens and dots (RFC 1123 §2.1), which an IPv4 address is too, or an IPv6 literal between
 * brackets.
 *
 * The reg-name of RFC 3986 admits more, and none of it is a host here: its sub-delimiters, the ";" that ends a
 * directive of a policy and the "," that ends a policy among them, a percent-encoded octet, and a name outside ASCII,
 * which the DNS knows by its A-label (RFC 5890 §2.3.2.1). One rule for every package that reads such a URL, the
 * endpoints of a SAML service provider, the URIs a client registers and the origins a page names, so that what one of
 * them accepts the others never refuse.
 */
final readonly class Host
{
    /**
     * The grammar without its anchors, for a pattern that reads the URL around the host.
     */
    public const string PATTERN = '(?:[A-Za-z0-9.-]+|\[[0-9A-Fa-f:.]+\])';

    public static function isHost(string $host): bool
    {
        return preg_match('~^' . self::PATTERN . '\z~', $host) === 1;
    }
}
