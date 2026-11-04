<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * OIDC Core §5.1
 */
final readonly class MiddleName extends ProfileClaim
{
    public function name(): string
    {
        return 'middle_name';
    }

    public function of(User $user): ?string
    {
        return $user->profile->middleName;
    }

    public function field(): ClaimField
    {
        return ClaimField::text();
    }
}
