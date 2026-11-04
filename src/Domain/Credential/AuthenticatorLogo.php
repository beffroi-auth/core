<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Exception\InvalidArgumentException;
use function preg_match;

/**
 * RFC 2397, FIDO Metadata Statement v3.1.1 §4
 */
final readonly class AuthenticatorLogo
{
    /**
     * Shown as an image, an SVG runs no script and fetches nothing; possessive, so a long image costs one pass.
     */
    private const string DATA_IMAGE = '/^data:image\/(?:png|svg\+xml);base64,[A-Za-z0-9+\/]++={0,2}$/D';

    public function __construct(
        public string $light,
        public string $dark,
    ) {
        if (preg_match(self::DATA_IMAGE, $light) !== 1 || preg_match(self::DATA_IMAGE, $dark) !== 1) {
            throw new InvalidArgumentException('The logo of an authenticator is a PNG or an SVG image in a base64 data: URL (RFC 2397).');
        }
    }

    public static function single(string $image): self
    {
        return new self($image, $image);
    }

    public function hasDarkVariant(): bool
    {
        return $this->dark !== $this->light;
    }
}
