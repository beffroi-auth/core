<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

/**
 * The OIDC Core §5.1.1 address claim; an address with no field at all is not an address.
 */
final readonly class Address
{
    public function __construct(
        public ?string $formatted = null,
        public ?string $streetAddress = null,
        public ?string $locality = null,
        public ?string $region = null,
        public ?string $postalCode = null,
        public ?string $country = null,
    ) {
    }

    public function isEmpty(): bool
    {
        return (
            $this->formatted === null
            && $this->streetAddress === null
            && $this->locality === null
            && $this->region === null
            && $this->postalCode === null
            && $this->country === null
        );
    }
}
