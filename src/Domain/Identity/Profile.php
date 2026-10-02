<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use Beffroi\Core\Exception\InvalidArgumentException;
use function preg_match;
use function sprintf;

/**
 * The standard profile of OIDC Core §5.1, protocol-neutral; the mapping to claim names belongs to the modules.
 */
final readonly class Profile
{
    public function __construct(
        public ?string $name = null,
        public ?string $givenName = null,
        public ?string $familyName = null,
        public ?string $middleName = null,
        public ?string $nickname = null,
        public ?string $preferredUsername = null,
        public ?string $profileUrl = null,
        public ?string $pictureUrl = null,
        public ?string $websiteUrl = null,
        public ?string $gender = null,
        public ?string $birthdate = null,
        public ?string $zoneinfo = null,
        public ?string $locale = null,
    ) {
        if ($birthdate !== null && preg_match('/^\d{4}(?:-\d{2}-\d{2})?$/', $birthdate) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'The birthdate "%s" must be "YYYY-MM-DD" or "YYYY" (a year alone, or "0000" for an unknown year).',
                $birthdate,
            ));
        }
    }

    public static function empty(): self
    {
        return new self();
    }
}
