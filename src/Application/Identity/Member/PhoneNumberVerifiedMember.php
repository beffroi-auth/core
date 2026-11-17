<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity\Member;

use Beffroi\Core\Domain\Identity\User;
use DateTimeImmutable;

/**
 * OIDC Core §5.1: a number the administrator writes takes back the verification of the previous one, as the person's
 * does, until they say it was verified.
 */
final readonly class PhoneNumberVerifiedMember implements MemberInterface
{
    public const string NAME = 'phone_number_verified';

    public function name(): string
    {
        return self::NAME;
    }

    public function heldAs(): string
    {
        return 'phone_number';
    }

    public function of(User $user): array
    {
        return [
            self::NAME => Said::written($user->phoneNumber === null ? null : $user->phoneNumberVerified),
        ];
    }

    public function say(User $user, mixed $value, DateTimeImmutable $now): void
    {
        $verified = Said::flag($value, self::NAME);
        if ($verified === $user->phoneNumberVerified) {
            return;
        }
        $user->changePhoneNumber($user->phoneNumber, $verified, $now);
    }
}
