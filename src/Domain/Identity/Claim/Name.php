<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * The full name of the person, as they write it (OIDC Core §5.1).
 */
final readonly class Name extends ProfileClaim
{
    public function name(): string
    {
        return 'name';
    }

    public function of(User $user): ?string
    {
        return $user->profile->name;
    }

    public function field(): ClaimField
    {
        return ClaimField::text();
    }
}
