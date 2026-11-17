<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity\Member;

use Beffroi\Core\Domain\Identity\User;
use DateTimeImmutable;

/**
 * OIDC Core §5.1
 */
final readonly class EmailVerifiedMember implements MemberInterface
{
    public const string NAME = 'email_verified';

    public function name(): string
    {
        return self::NAME;
    }

    public function heldAs(): string
    {
        return EmailMember::NAME;
    }

    public function of(User $user): array
    {
        return [
            self::NAME => Said::written($user->email === null ? null : $user->emailVerified),
        ];
    }

    public function say(User $user, mixed $value, DateTimeImmutable $now): void
    {
        $verified = Said::flag($value, self::NAME);
        if ($verified === $user->emailVerified) {
            return;
        }
        $user->changeEmail($user->email, $verified, $now);
    }
}
