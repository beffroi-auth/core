<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Domain\Identity\User;

/**
 * The language the person reads, which this provider writes to them in when no request of theirs says one (UserLocale, issue #471).
 */
final readonly class Locale extends ProfileClaim
{
    public function name(): string
    {
        return 'locale';
    }

    public function of(User $user): ?string
    {
        return $user->profile->locale;
    }
}
