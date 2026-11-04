<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Tenant;

use Beffroi\Core\Domain\Encoding\Host;
use Beffroi\Core\Exception\InvalidArgumentException;
use function is_string;
use function parse_url;
use function sprintf;
use Stringable;
use function strtolower;

/**
 * Its origin is named in a Content-Security-Policy, so a host holding a ";" or a ",", which RFC 3986 §3.2.2
 * admits, is refused.
 */
final readonly class LogoUri implements Stringable
{
    public string $value;

    /**
     * RFC 6454 §3.2
     */
    public string $origin;

    public function __construct(string $value)
    {
        $parts = parse_url($value);
        if ($parts === false || ! isset($parts['scheme'], $parts['host']) || ! is_string($parts['host'])) {
            throw new InvalidArgumentException(sprintf('The logo URL "%s" of a tenant is not an absolute URL.', $value));
        }
        if (isset($parts['fragment'])) {
            throw new InvalidArgumentException(sprintf('The logo URL "%s" of a tenant must not carry a fragment.', $value));
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException(sprintf('The logo URL "%s" of a tenant must not carry credentials.', $value));
        }
        if (! Host::isHost($parts['host'])) {
            throw new InvalidArgumentException(sprintf(
                'The logo URL "%s" of a tenant must name a host of letters, digits, hyphens and dots, or an IP address (RFC 3986 §3.2.2).',
                $value,
            ));
        }
        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        if ($scheme !== 'https' && ! ($scheme === 'http' && Issuer::isLoopbackHost($host))) {
            throw new InvalidArgumentException(sprintf(
                'The logo URL "%s" of a tenant must use HTTPS; plain HTTP is only accepted on a loopback host.',
                $value,
            ));
        }

        $this->value = $value;
        $this->origin = $scheme . '://' . $host . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
