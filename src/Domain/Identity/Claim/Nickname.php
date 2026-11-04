<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * OIDC Core §5.1
 */
final readonly class Nickname extends ProfileClaim
{
    public function name(): string
    {
        return 'nickname';
    }

    public function of(User $user): ?string
    {
        return $user->profile->nickname;
    }

    public function field(): ClaimField
    {
        return ClaimField::text();
    }
}
