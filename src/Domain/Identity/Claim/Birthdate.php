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
     * A date, which a browser offers a calendar for (issue #479). The pattern comes with it, for a browser
     * that renders a line of text instead and for which it is then the only check; it is the pattern the
     * profile itself refuses anything else by, which is wider than a date input, OIDC Core §5.1 providing
     * for a year alone and for "0000" when nobody knows it. A birthdate held in that wider shape is
     * offered as a line of text rather than erased by a calendar that cannot render it
     * (`ClaimField::forHolding()`).
     */
    public function field(): ClaimField
    {
        return ClaimField::date('\\d{4}(-\\d{2}-\\d{2})?', 'profile.birthdate.example');
    }
}
