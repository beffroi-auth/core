<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Tenant;

use Beffroi\Core\Exception\InvalidArgumentException;
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
 * OIDC Discovery §3, RFC 8414 §2
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
     * RFC 6454 §3.2
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
     * RFC 6761 §6.3
     *
     * @param string $host as parse_url() gives it, lowercased; an IPv6 literal keeps its brackets
     */
    public static function isLoopbackHost(string $host): bool
    {
        if (in_array($host, ['localhost', '[::1]'], true) || str_ends_with($host, '.localhost')) {
            return true;
        }

        return preg_match('/^127\.\d{1,3}\.\d{1,3}\.\d{1,3}$/D', $host) === 1;
    }
}
