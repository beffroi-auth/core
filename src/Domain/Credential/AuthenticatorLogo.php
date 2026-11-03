<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Exception\InvalidArgumentException;
use function preg_match;

/**
 * The logo of a model of authenticator, as a source of names gives it: an image in a data: URL (RFC 2397), PNG
 * as the FIDO metadata carry it (FIDO Metadata Statement v3.1.1 §4, "icon") or SVG as the list of the community
 * does, in base64, so that a page shows it without fetching anything and under an "img-src" that admits
 * "data:". One image for a light page and one for a dark page, the same one twice when the source has one.
 */
final readonly class AuthenticatorLogo
{
    /**
     * An image that loads nothing else: a raster, or an SVG, which a page shows as an image, where it runs no
     * script and fetches nothing. Possessive, so that a long image costs one pass.
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

    /**
     * The logo of a source that has one image for every page.
     */
    public static function single(string $image): self
    {
        return new self($image, $image);
    }

    /**
     * Whether a dark page shows another image than a light one.
     */
    public function hasDarkVariant(): bool
    {
        return $this->dark !== $this->light;
    }
}
