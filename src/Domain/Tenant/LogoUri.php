<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Tenant;

use Beffroi\Core\Exception\InvalidArgumentException;
use function is_string;
use function parse_url;
use function sprintf;
use Stringable;
use function strtolower;

/**
 * Where the logo of a tenant is served from (issue #449): an absolute HTTPS URL, no fragment, no
 * credentials; plain HTTP on a loopback host only, as the issuer of a development stack is.
 *
 * The browser of whoever reads the page loads it, never the provider, so what this holds is handed to a
 * browser and named in a Content-Security-Policy; that is why the origin is derived here, once, and not
 * by whoever writes a policy.
 *
 * The same rails guard what a client registers (ClientUri), and the two are not one class: that one
 * belongs to the protocol, refuses in the words of RFC 6749 §3.1.2 and answers questions about a
 * redirection; this one is of the core, which knows no protocol, and answers one question about an image.
 */
final readonly class LogoUri implements Stringable
{
    public string $value;

    /**
     * RFC 6454 §3.2: the scheme, the host and the port, which is what a source expression of a policy
     * names (CSP Level 3 §2.3) and all it may name.
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
