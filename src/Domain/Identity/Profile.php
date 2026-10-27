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
 * The standard profile of OIDC Core §5.1, protocol-neutral; the mapping to claim names belongs to the modules.
 */
final readonly class Profile
{
    /**
     * What a year withheld is read as when a day is checked: a leap year, so that the 29th of February stays a
     * day somebody who keeps their year to themselves was born on.
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
     * OIDC Core §5.1: "YYYY-MM-DD", or "YYYY" when only the year is given, the year "0000" when it is withheld.
     * A date names a day of the calendar, and its shape alone does not make it one (issue #846): "2024-13-45" is
     * refused as "21/05/1990" is.
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
     * The document a column holds a profile as. **It names no claim and no field.** The document is the
     * properties of the object, written by their own names and read back as named arguments of its
     * constructor, so a claim added to this object is a claim the document carries without a line: the
     * names of §5.1 are matched with the fields that hold them in `describedWith()`, and a fourth place
     * to be wrong is not opened here.
     *
     * Every property of this object is public and holds a string or nothing, which is the convention of a
     * value object here, so what `get_object_vars()` reads is what a reader of the object sees. What a
     * profile holds nothing of is absent from the document rather than written null, which is what the
     * constructor already answers for.
     *
     * @return array<array-key, string> keyed by the names of the properties that hold a claim
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
     * A key the constructor does not answer to, or a value that is not a string, is a profile this
     * provider did not write and never a guess.
     *
     * @param array<array-key, mixed> $document
     *
     * @throws InvalidArgumentException when the document is not what this object writes
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
     * The same profile with the claims given said otherwise (issue #452): a claim absent from the map is
     * left as it was, and a claim present with null is one the person took back.
     *
     * The one place the names of OIDC Core §5.1 are matched with the fields that hold them, with `claims()`
     * beside it for the other direction, because this is the object whose shape they describe; every claim of
     * Domain\Identity\Claim writes through here, so a claim added is a class added and a line here and
     * there, and never a reading of a field by name.
     *
     * **A key here is a name of the specification and never a field of this object** (issue #626): three
     * of them differ, "profile", "picture" and "website" being held by `profileUrl`, `pictureUrl` and
     * `websiteUrl`, and a field is nobody's claim. So what a caller writes is what a module announces and
     * what a tenant opens, and the name of a field stops existing outside this object.
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
     * What the profile says under each name of OIDC Core §5.1, null where it says nothing: the inverse of
     * `describedWith()`, written beside it so that the two directions of one match are read together, and
     * held together by a test that says every claim back through the other.
     *
     * It is what a caller compares claim by claim, by the names a source holds them under (`UserSource`),
     * and never a reading of a field by name.
     *
     * @return array<string, string|null>
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
