<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use Beffroi\Core\Exception\InvalidArgumentException;
use function preg_match;
use function sprintf;

/**
 * What a GeoIP database says about an address: approximate, never a proof.
 */
final readonly class GeoLocation
{
    public function __construct(
        public ?string $countryCode = null,
        public ?string $region = null,
        public ?string $city = null,
        public ?int $autonomousSystemNumber = null,
        public ?string $autonomousSystemOrganization = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {
        if ($countryCode !== null && preg_match('/^[A-Z]{2}$/', $countryCode) !== 1) {
            throw new InvalidArgumentException(sprintf('The country code "%s" must be two uppercase letters (ISO 3166-1 alpha-2).', $countryCode));
        }
        if (($latitude === null) !== ($longitude === null)) {
            throw new InvalidArgumentException('Latitude and longitude come together or not at all.');
        }
        if ($latitude !== null && ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180)) {
            throw new InvalidArgumentException('Coordinates are out of range.');
        }
    }
}
