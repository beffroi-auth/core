<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Confirmation;

use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Domain\Identity\User;
use DateInterval;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class ConfirmableEmail implements ConfirmableAddressInterface
{
    /**
     * NIST SP 800-63B-4 §4.2.1.2: twenty-four hours at most when sent to an email address.
     */
    public const string LIFETIME = 'PT24H';

    public function claim(): string
    {
        return 'email';
    }

    public function of(User $user): ?string
    {
        return $user->email?->value;
    }

    public function isVerified(User $user): bool
    {
        return $user->verifiedEmail() !== null;
    }

    public function establish(User $user, DateTimeImmutable $now): void
    {
        $user->changeEmail($user->email, true, $now);
    }

    public function draw(Uuid $id, User $user, PasswordHash $code, PasswordHash $link, DateTimeImmutable $now): EmailConfirmation
    {
        return new EmailConfirmation($id, $user, $code, $link, $now, $now->add(new DateInterval(self::LIFETIME)));
    }
}
