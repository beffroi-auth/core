<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;
use DateTimeImmutable;

/**
 * OIDC Core §5.1
 *
 * A closed set is enforced here, for every caller: a form that offers a list can be sent with anything.
 */
abstract readonly class ProfileClaim implements ClaimInterface
{
    public function describe(User $user, ?string $value, DateTimeImmutable $now): void
    {
        $this->field()->assertHolds($value);
        $user->updateProfile($user->profile->describedWith([
            $this->name() => $value,
        ]), $now);
    }
}
