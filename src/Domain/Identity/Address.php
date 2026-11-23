<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use Beffroi\Core\Domain\Encoding\Document;
use Beffroi\Core\Domain\Encoding\PlainText;
use Beffroi\Core\Exception\InvalidArgumentException;
use function sprintf;

/**
 * OIDC Core §5.1.1
 *
 * "formatted" and "street_address" may hold several lines, which the specification separates by a line feed.
 */
final readonly class Address
{
    /**
     * @throws InvalidArgumentException when a member is not plain text, or too long
     */
    public function __construct(
        public ?string $formatted = null,
        public ?string $streetAddress = null,
        public ?string $locality = null,
        public ?string $region = null,
        public ?string $postalCode = null,
        public ?string $country = null,
    ) {
        foreach ([
            'formatted' => $formatted,
            'street_address' => $streetAddress,
        ] as $member => $lines) {
            if ($lines !== null && ! PlainText::holdsLines($lines, PlainText::MAX_LINES_LENGTH)) {
                throw new InvalidArgumentException(sprintf(
                    'The %s of an address must be plain text of at most %d characters, its lines separated by line feeds, with no other control, format or separator character.',
                    $member,
                    PlainText::MAX_LINES_LENGTH,
                ));
            }
        }
        foreach ([
            'locality' => $locality,
            'region' => $region,
            'postal_code' => $postalCode,
            'country' => $country,
        ] as $member => $line) {
            if ($line !== null && ! PlainText::holds($line, PlainText::MAX_LENGTH)) {
                throw new InvalidArgumentException(sprintf(
                    'The %s of an address must be plain text of at most %d characters, with no control, format or line separator character.',
                    $member,
                    PlainText::MAX_LENGTH,
                ));
            }
        }
    }

    /**
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
     * @throws InvalidArgumentException
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
