<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * OIDC Core §5.1
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

    public function field(): ClaimField
    {
        return ClaimField::date('\\d{4}(-\\d{2}-\\d{2})?', 'profile.birthdate.example');
    }
}
