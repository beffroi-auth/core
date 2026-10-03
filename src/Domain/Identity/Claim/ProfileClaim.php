<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;
use DateTimeImmutable;

/**
 * A claim the profile of OIDC Core §5.1 holds (issue #452). Every one of them is written the same way,
 * which is why that is here and once: the profile is held whole, so saying one claim is a copy of it with
 * that one claim replaced, and a claim that is written otherwise does not extend this.
 *
 * A claim whose set is closed refuses a value the set does not hold, here and for every caller (issue
 * #479): what a page offers is a list, and a page that offers a list is a page whose form can be sent
 * with anything (decision 0006). What the shape of a value is, where the set is not closed, stays the
 * model's: a birthdate that is not a date is refused by the profile that holds it.
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
