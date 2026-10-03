<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * A site of the person (OIDC Core §5.1, "website").
 */
final readonly class WebsiteUrl extends ProfileClaim
{
    public function name(): string
    {
        return 'website_url';
    }

    public function of(User $user): ?string
    {
        return $user->profile->websiteUrl;
    }

    public function field(): ClaimField
    {
        return ClaimField::url();
    }
}
