<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Archive\Chapter;

use Beffroi\Core\Domain\Archive\ArchivedFacts;
use Beffroi\Core\Domain\Identity\Address;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Identity\UserSource;
use Beffroi\Core\Port\ArchiveChapterInterface;

/**
 * OIDC Core §5.1, RGPD article 20
 *
 * Reads the record and not the wired claims: a claim no page offers is held all the same.
 */
final readonly class ProfileChapter implements ArchiveChapterInterface
{
    public function name(): string
    {
        return 'profile';
    }

    public function of(User $user): array
    {
        $profile = $user->profile;

        return [
            [
                'sub' => $user->subject->value,
                'username' => $user->username,
                'name' => $profile->name,
                'given_name' => $profile->givenName,
                'family_name' => $profile->familyName,
                'middle_name' => $profile->middleName,
                'nickname' => $profile->nickname,
                'preferred_username' => $profile->preferredUsername,
                'profile' => $profile->profileUrl,
                'picture' => $profile->pictureUrl,
                'website' => $profile->websiteUrl,
                'email' => $user->email?->value,
                'email_verified' => $user->email === null ? null : $user->emailVerified,
                'gender' => $profile->gender,
                'birthdate' => $profile->birthdate,
                'zoneinfo' => $profile->zoneinfo,
                'locale' => $profile->locale,
                'phone_number' => $user->phoneNumber,
                'phone_number_verified' => $user->phoneNumber === null ? null : $user->phoneNumberVerified,
                'address' => self::address($user->address),
                'created_at' => ArchivedFacts::moment($user->createdAt),
                'updated_at' => ArchivedFacts::moment($user->updatedAt),
                'disabled_at' => ArchivedFacts::moment($user->disabledAt),
                'source' => self::source($user->source),
            ],
        ];
    }

    /**
     * @return array{provider: string|null, claims: list<string>}
     */
    private static function source(UserSource $source): array
    {
        return [
            'provider' => $source->provider,
            'claims' => $source->claims,
        ];
    }

    /**
     * OIDC Core §5.1.1
     *
     * @return array<string, string|null>|null
     */
    private static function address(?Address $address): ?array
    {
        if ($address === null) {
            return null;
        }

        return [
            'formatted' => $address->formatted,
            'street_address' => $address->streetAddress,
            'locality' => $address->locality,
            'region' => $address->region,
            'postal_code' => $address->postalCode,
            'country' => $address->country,
        ];
    }
}
