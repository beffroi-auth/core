<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * OIDC Core §5.1, "picture"
 *
 * Never fetched by this provider.
 */
final readonly class PictureUrl extends ProfileClaim
{
    public function name(): string
    {
        return 'picture';
    }

    public function of(User $user): ?string
    {
        return $user->profile->pictureUrl;
    }

    public function field(): ClaimField
    {
        return ClaimField::url();
    }
}
