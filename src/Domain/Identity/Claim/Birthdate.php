<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * The birthdate, which the profile refuses unless it is a date or a year (OIDC Core §5.1).
 */
final readonly class Birthdate extends ProfileClaim
{
    public function name(): string
    {
        return 'birthdate';
    }

    public function of(User $user): ?string
    {
        return $user->profile->birthdate;
    }

    /**
     * A line of text and not a date box, on purpose: OIDC Core §5.1 provides for a year alone, and "0000"
     * for a year nobody knows, neither of which an HTML date input can express. So what the browser checks
     * is the pattern the profile itself refuses anything else by, and the page shows an example.
     */
    public function field(): ClaimField
    {
        return ClaimField::text('\\d{4}(-\\d{2}-\\d{2})?', 'profile.birthdate.example');
    }
}
