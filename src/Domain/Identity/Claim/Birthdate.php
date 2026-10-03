<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * The birthdate, which the profile refuses unless it is a date or a year (OIDC Core §5.1).
 */
final readonly class Birthdate extends ProfileClaim
{
    public function name(): string
    {
        return 'birthdate';
    }

    public function of(User $user): ?string
    {
        return $user->profile->birthdate;
    }
}
