<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * OIDC Core §5.1
 *
 * A free string on purpose.
 */
final readonly class Gender extends ProfileClaim
{
    public function name(): string
    {
        return 'gender';
    }

    public function of(User $user): ?string
    {
        return $user->profile->gender;
    }

    public function field(): ClaimField
    {
        return ClaimField::text();
    }
}
