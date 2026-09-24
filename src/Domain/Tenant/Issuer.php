<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Tenant;

use Heidi\Core\Exception\InvalidArgumentException;
use function in_array;
use function is_string;
use function parse_url;
use function preg_match;
use function sprintf;
use function str_ends_with;
use Stringable;
use function strlen;
use function strtolower;
use function substr;
use function trim;

/**
 * The issuer identifier of a tenant (OIDC Discovery §3, RFC 8414 §2): an absolute HTTPS URL with no query
 * and no fragment. Plain HTTP is tolerated on a loopback host only (RFC 6761 §6.3).
 */
final readonly class Issuer implements Stringable
{
    public string $value;

    public string $host;

    public string $path;

    public function __construct(string $value)
    {
        $value = trim($value);
        $parts = parse_url($value);
        if ($parts === false || ! isset($parts['scheme'], $parts['host']) || ! is_string($parts['host'])) {
            throw new InvalidArgumentException(sprintf('The issuer "%s" is not an absolute URL.', $value));
        }
        if (isset($parts['query']) || isset($parts['fragment'])) {
            throw new InvalidArgumentException(sprintf('The issuer "%s" must not carry a query or a fragment.', $value));
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException(sprintf('The issuer "%s" must not carry credentials.', $value));
        }
        $host = strtolower($parts['host']);
        $scheme = strtolower($parts['scheme']);
        if ($scheme !== 'https' && ! ($scheme === 'http' && self::isLoopbackHost($host))) {
            throw new InvalidArgumentException(sprintf('The issuer "%s" must use HTTPS; plain HTTP is only accepted on a loopback host.', $value));
        }
        $path = $parts['path'] ?? '';
        if (str_ends_with($path, '/')) {
            throw new InvalidArgumentException(sprintf('The issuer "%s" must not end with a slash: clients compare it byte for byte.', $value));
        }

        $this->value = $value;
        $this->host = $host;
        $this->path = $path;
    }

    /**
     * The issuer without its path: the scheme, the host and the port it names, which is what a browser
     * calls the origin (RFC 6454 §3.2). The one place it is derived, because everything that speaks of
     * this provider to a browser has to say the same thing: the origin a WebAuthn ceremony accepts
     * (WebAuthn Level 3 §13.4.9), the one the metadata of RFC 8414 §3 hangs its path under, and the one
     * a router builds an absolute URL from. Two of them disagreeing is a ceremony that refuses without
     * saying why.
     */
    public function origin(): string
    {
        return substr($this->value, 0, strlen($this->value) - strlen($this->path));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * RFC 6761 §6.3: "localhost" and every name under it, 127.0.0.0/8 and ::1.
     *
     * @param string $host as parse_url() gives it, lowercased; an IPv6 literal keeps its brackets
     */
    public static function isLoopbackHost(string $host): bool
    {
        if (in_array($host, ['localhost', '[::1]'], true) || str_ends_with($host, '.localhost')) {
            return true;
        }

        return preg_match('/^127\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $host) === 1;
    }
}
