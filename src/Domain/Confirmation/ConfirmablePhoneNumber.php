<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Confirmation;

use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Domain\Identity\User;
use DateInterval;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class ConfirmablePhoneNumber implements ConfirmableAddressInterface
{
    /**
     * NIST SP 800-63B-4 §4.2.1.2: ten minutes at most when sent by text message.
     */
    public const string LIFETIME = 'PT10M';

    public function claim(): string
    {
        return 'phone_number';
    }

    public function of(User $user): ?string
    {
        return $user->phoneNumber;
    }

    public function isVerified(User $user): bool
    {
        return $user->verifiedPhoneNumber() !== null;
    }

    public function establish(User $user, DateTimeImmutable $now): void
    {
        $user->changePhoneNumber($user->phoneNumber, true, $now);
    }

    public function draw(Uuid $id, User $user, PasswordHash $code, PasswordHash $link, DateTimeImmutable $now): PhoneNumberConfirmation
    {
        return new PhoneNumberConfirmation($id, $user, $code, $link, $now, $now->add(new DateInterval(self::LIFETIME)));
    }
}
