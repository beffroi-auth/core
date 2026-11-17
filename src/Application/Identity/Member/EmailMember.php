<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity\Member;

use Beffroi\Core\Domain\Identity\EmailAddress;
use Beffroi\Core\Domain\Identity\User;
use DateTimeImmutable;

/**
 * OIDC Core §5.1: a new address is unverified until the administrator says it was verified.
 */
final readonly class EmailMember implements MemberInterface
{
    public const string NAME = 'email';

    public function name(): string
    {
        return self::NAME;
    }

    public function heldAs(): string
    {
        return self::NAME;
    }

    public function of(User $user): array
    {
        return [
            self::NAME => $user->email?->value,
        ];
    }

    public function say(User $user, mixed $value, DateTimeImmutable $now): void
    {
        $value = Said::text($value, self::NAME);
        if ($value === $user->email?->value) {
            return;
        }
        $user->changeEmail($value === null ? null : new EmailAddress($value), false, $now);
    }
}
