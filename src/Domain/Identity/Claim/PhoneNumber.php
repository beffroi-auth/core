<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;
use DateTimeImmutable;

/**
 * The telephone number of the person (OIDC Core §5.1). It is not a claim of the profile and does not
 * extend ProfileClaim, for the reason it is written differently: a number carries whether this provider
 * has verified it, and a person who writes a new one has verified nothing.
 *
 * So saying it takes the verification back, which is the only honest answer: what was proved was proved of
 * the number that is gone. A deployment that verifies numbers verifies this one again afterwards, and
 * until then no client is told that it is verified.
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
}
