<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use Beffroi\Core\Exception\InvalidArgumentException;
use function array_key_exists;
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

    /**
     * The same profile with the claims given said otherwise (issue #452): a claim absent from the map is
     * left as it was, and a claim present with null is one the person took back.
     *
     * The one place the names of OIDC Core §5.1 are matched with the fields that hold them, because this is
     * the object whose shape they describe; every claim of Domain\Identity\Claim writes through here, so a
     * claim added is a class added and a line here, and never a reading of a field by name.
     *
     * @param array<string, string|null> $claims
     */
    public function describedWith(array $claims): self
    {
        return new self(
            array_key_exists('name', $claims) ? $claims['name'] : $this->name,
            array_key_exists('given_name', $claims) ? $claims['given_name'] : $this->givenName,
            array_key_exists('family_name', $claims) ? $claims['family_name'] : $this->familyName,
            array_key_exists('middle_name', $claims) ? $claims['middle_name'] : $this->middleName,
            array_key_exists('nickname', $claims) ? $claims['nickname'] : $this->nickname,
            array_key_exists('preferred_username', $claims) ? $claims['preferred_username'] : $this->preferredUsername,
            array_key_exists('profile_url', $claims) ? $claims['profile_url'] : $this->profileUrl,
            array_key_exists('picture_url', $claims) ? $claims['picture_url'] : $this->pictureUrl,
            array_key_exists('website_url', $claims) ? $claims['website_url'] : $this->websiteUrl,
            array_key_exists('gender', $claims) ? $claims['gender'] : $this->gender,
            array_key_exists('birthdate', $claims) ? $claims['birthdate'] : $this->birthdate,
            array_key_exists('zoneinfo', $claims) ? $claims['zoneinfo'] : $this->zoneinfo,
            array_key_exists('locale', $claims) ? $claims['locale'] : $this->locale,
        );
    }

    /**
     * The same profile, read in another language (issue #471): what the person said of themselves is
     * untouched, the "locale" claim alone being replaced. A copy and not a setter, a profile being held
     * whole by whoever holds it, and the rule of the claim living where the claim does.
     */
    public function readIn(string $locale): self
    {
        if ($locale === '') {
            throw new InvalidArgumentException('The language a profile is read in is either absent or non-empty.');
        }

        return $this->describedWith([
            'locale' => $locale,
        ]);
    }
}
