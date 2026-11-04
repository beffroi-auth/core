<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * OIDC Core §5.1
 *
 * Not what the person signs in with: nothing resolves an account by it.
 */
final readonly class PreferredUsername extends ProfileClaim
{
    public function name(): string
    {
        return 'preferred_username';
    }

    public function of(User $user): ?string
    {
        return $user->profile->preferredUsername;
    }

    public function field(): ClaimField
    {
        return ClaimField::text();
    }
}
