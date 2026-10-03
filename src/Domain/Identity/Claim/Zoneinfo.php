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
}
