<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use Beffroi\Core\Domain\Encoding\Document;
use Beffroi\Core\Exception\InvalidArgumentException;

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

    /**
     * The document a column holds an address as: the six fields of §5.1.1 under their own names, written
     * whether they are there or not, so that what the column holds reads as the claim does.
     *
     * @return array<string, string|null>
     */
    public function toDocument(): array
    {
        return [
            'formatted' => $this->formatted,
            'street_address' => $this->streetAddress,
            'locality' => $this->locality,
            'region' => $this->region,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
        ];
    }

    /**
     * @param array<array-key, mixed> $document
     *
     * @throws InvalidArgumentException when a field of the document is not a string
     */
    public static function fromDocument(array $document): self
    {
        $members = new Document($document);

        return new self(
            $members->nullableString('formatted'),
            $members->nullableString('street_address'),
            $members->nullableString('locality'),
            $members->nullableString('region'),
            $members->nullableString('postal_code'),
            $members->nullableString('country'),
        );
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
