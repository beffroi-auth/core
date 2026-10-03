<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;
use DateTimeImmutable;

/**
 * A claim the profile of OIDC Core §5.1 holds (issue #452). Every one of them is written the same way,
 * which is why that is here and once: the profile is held whole, so saying one claim is a copy of it with
 * that one claim replaced, and a claim that is written otherwise does not extend this.
 */
abstract readonly class ProfileClaim implements ClaimInterface
{
    public function describe(User $user, ?string $value, DateTimeImmutable $now): void
    {
        $user->updateProfile($user->profile->describedWith([
            $this->name() => $value,
        ]), $now);
    }
}
