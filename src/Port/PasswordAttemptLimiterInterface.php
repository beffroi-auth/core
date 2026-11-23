<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;

/**
 * Keyed by the user and never by the address, which a whole organisation may share; an unknown username is
 * counted nowhere, the decoy hash already making it cost a wrong password. The count is atomic and shared by every
 * process of a deployment: attempts made at once are counted one by one (RFC 4226 §7.3).
 */
interface PasswordAttemptLimiterInterface
{
    public function attempt(User $user): bool;

    public function reset(User $user): void;
}
