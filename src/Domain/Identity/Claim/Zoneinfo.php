<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

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
     * A line of text with an example, and not a choice of the six hundred names of the zoneinfo database:
     * a list that long is a picker, which is a matter of look or of script and belongs to whoever dresses
     * these pages (architecture §5.2).
     */
    public function field(): ClaimField
    {
        return ClaimField::text('[A-Za-z]+(/[A-Za-z_+-]+)+', 'profile.zoneinfo.example');
    }
}
