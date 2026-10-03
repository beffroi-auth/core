<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * What the person says of their gender, a free string on purpose (OIDC Core §5.1).
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
