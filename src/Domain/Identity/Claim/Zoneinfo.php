<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;
use function array_combine;
use DateTimeZone;

/**
 * OIDC Core §5.1
 */
final readonly class Zoneinfo extends ProfileClaim
{
    public function name(): string
    {
        return 'zoneinfo';
    }

    public function of(User $user): ?string
    {
        return $user->profile->zoneinfo;
    }

    public function field(): ClaimField
    {
        $names = DateTimeZone::listIdentifiers();

        return ClaimField::oneOf(array_combine($names, $names));
    }
}
