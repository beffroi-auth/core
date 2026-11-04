<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use Beffroi\Core\Exception\InvalidArgumentException;
use function array_key_exists;
use function checkdate;
use Error;
use function get_object_vars;
use function is_string;
use function preg_match;
use function sprintf;

/**
 * OIDC Core §5.1
 */
final readonly class Profile
{
    /**
     * A leap year, so that 0000-02-29 stays a valid birthdate.
     */
    private const int WITHHELD_YEAR_READ_AS = 2000;

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
        if ($birthdate !== null && ! self::isABirthdate($birthdate)) {
            throw new InvalidArgumentException(sprintf(
                'The birthdate "%s" must be a day of the calendar, "YYYY-MM-DD", or a year alone, "YYYY", the year "0000" when it is withheld.',
                $birthdate,
            ));
        }
    }

    public static function empty(): self
    {
        return new self();
    }

    /**
     * OIDC Core §5.1
     */
    private static function isABirthdate(string $birthdate): bool
    {
        if (preg_match('/^\d{4}$/D', $birthdate) === 1) {
            return true;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $birthdate, $date) !== 1) {
            return false;
        }
        [, $year, $month, $day] = $date;

        return checkdate((int) $month, (int) $day, $year === '0000' ? self::WITHHELD_YEAR_READ_AS : (int) $year);
    }

    /**
     * Read back as named arguments of the constructor: every property must be one of its parameters.
     *
     * @return array<array-key, string> by property name
     */
    public function toDocument(): array
    {
        $document = [];
        foreach (get_object_vars($this) as $name => $claim) {
            if (is_string($claim)) {
                $document[$name] = $claim;
            }
        }

        return $document;
    }

    /**
     * @param array<array-key, mixed> $document
     *
     * @throws InvalidArgumentException
     */
    public static function fromDocument(array $document): self
    {
        $named = [];
        foreach ($document as $name => $claim) {
            if (! is_string($name) || ! is_string($claim)) {
                throw new InvalidArgumentException('A profile is a document of claims named by the fields that hold them, each a string.');
            }
            $named[$name] = $claim;
        }

        try {
            return new self(...$named);
        } catch (Error $error) {
            throw new InvalidArgumentException($error->getMessage(), previous: $error);
        }
    }

    /**
     * @param array<string, string|null> $claims by OIDC Core §5.1 name; null clears, absent leaves as is
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
            array_key_exists('profile', $claims) ? $claims['profile'] : $this->profileUrl,
            array_key_exists('picture', $claims) ? $claims['picture'] : $this->pictureUrl,
            array_key_exists('website', $claims) ? $claims['website'] : $this->websiteUrl,
            array_key_exists('gender', $claims) ? $claims['gender'] : $this->gender,
            array_key_exists('birthdate', $claims) ? $claims['birthdate'] : $this->birthdate,
            array_key_exists('zoneinfo', $claims) ? $claims['zoneinfo'] : $this->zoneinfo,
            array_key_exists('locale', $claims) ? $claims['locale'] : $this->locale,
        );
    }

    /**
     * @return array<string, string|null> by OIDC Core §5.1 name
     */
    public function claims(): array
    {
        return [
            'name' => $this->name,
            'given_name' => $this->givenName,
            'family_name' => $this->familyName,
            'middle_name' => $this->middleName,
            'nickname' => $this->nickname,
            'preferred_username' => $this->preferredUsername,
            'profile' => $this->profileUrl,
            'picture' => $this->pictureUrl,
            'website' => $this->websiteUrl,
            'gender' => $this->gender,
            'birthdate' => $this->birthdate,
            'zoneinfo' => $this->zoneinfo,
            'locale' => $this->locale,
        ];
    }

    /**
     * The same profile with only its "locale" claim replaced.
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
