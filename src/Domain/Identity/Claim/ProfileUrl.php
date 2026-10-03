<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * A page about the person, somewhere else (OIDC Core §5.1, "profile").
 */
final readonly class ProfileUrl extends ProfileClaim
{
    public function name(): string
    {
        return 'profile_url';
    }

    public function of(User $user): ?string
    {
        return $user->profile->profileUrl;
    }
}
