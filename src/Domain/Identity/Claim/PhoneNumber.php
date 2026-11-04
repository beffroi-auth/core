<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;
use DateTimeImmutable;

/**
 * OIDC Core §5.1
 *
 * Not a ProfileClaim: a number the person writes takes back the verification the previous one had.
 */
final readonly class PhoneNumber implements ClaimInterface
{
    public function name(): string
    {
        return 'phone_number';
    }

    public function of(User $user): ?string
    {
        return $user->phoneNumber;
    }

    public function describe(User $user, ?string $value, DateTimeImmutable $now): void
    {
        $user->changePhoneNumber($value, false, $now);
    }

    public function field(): ClaimField
    {
        return ClaimField::telephone();
    }
}
