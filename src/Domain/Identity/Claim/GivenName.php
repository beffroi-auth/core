<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * The given name, which is not the first word of the full name in every culture (OIDC Core §5.1).
 */
final readonly class GivenName extends ProfileClaim
{
    public function name(): string
    {
        return 'given_name';
    }

    public function of(User $user): ?string
    {
        return $user->profile->givenName;
    }
}
