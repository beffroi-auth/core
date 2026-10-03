<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * What the person would rather be called by a client; it is not what they sign in with, and nothing resolves an account by it (OIDC Core §5.1).
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
}
