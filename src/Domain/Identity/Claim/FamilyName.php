<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * The family name (OIDC Core §5.1).
 */
final readonly class FamilyName extends ProfileClaim
{
    public function name(): string
    {
        return 'family_name';
    }

    public function of(User $user): ?string
    {
        return $user->profile->familyName;
    }
}
