<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;
use function array_combine;
use DateTimeZone;

/**
 * The clock the person keeps, which every date shown to them is read on (UserTimeZone, issue #469).
 */
final readonly class Zoneinfo extends ProfileClaim
{
    public function name(): string
    {
        return 'zoneinfo';
    }

    public function of(User $user): ?string
    {
        return $user->profile->zoneinfo;
    }

    /**
     * The names of the zoneinfo database this PHP carries (issue #479), each its own label, a name of a
     * zone being what a person recognises it by. The set this closes on is the one a stored value is
     * already read against when a date is shown to that person (`UserTimeZone`), so the two agree instead
     * of one guarding the other: a zone nobody has is refused where it was stored and read as nothing.
     *
     * Several hundred names are typed into with the set suggested rather than chosen from a list, which
     * the field says of itself (`ClaimField::isPicked()`).
     */
    public function field(): ClaimField
    {
        $names = DateTimeZone::listIdentifiers();

        return ClaimField::oneOf(array_combine($names, $names));
    }
}
