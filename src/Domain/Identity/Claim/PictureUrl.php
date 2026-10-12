<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * A picture of the person, somewhere else; this provider never fetches it (OIDC Core §5.1, "picture").
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
