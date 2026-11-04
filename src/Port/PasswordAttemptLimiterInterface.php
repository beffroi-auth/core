<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;

/**
 * Keyed by the user and never by the address, which a whole organisation may share; an unknown username is
 * counted nowhere, the decoy hash already making it cost a wrong password.
 */
interface PasswordAttemptLimiterInterface
{
    public function attempt(User $user): bool;

    public function reset(User $user): void;
}
