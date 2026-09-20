<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Identity\User;

/**
 * How many codes a user may try in a window: a six-digit code is guessable, so the check is limited
 * before anything is verified (decision 0006). What the window is and how it is kept is the
 * implementation's (the rate limiter of Symfony in the bundle); the rule that there is one is the core's.
 */
interface CodeAttemptLimiterInterface
{
    /**
     * Counts one attempt of the user and tells whether it may go on.
     */
    public function attempt(User $user): bool;
}
