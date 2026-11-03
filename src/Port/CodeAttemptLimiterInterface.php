<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;

/**
 * How many codes a user may try in a window: a six-digit code is guessable, so the check is limited
 * before anything is verified (decision 0006). What the window is and how it is kept is the
 * implementation's (the rate limiter of Symfony in the application); the rule that there is one is the core's.
 */
interface CodeAttemptLimiterInterface
{
    /**
     * Tells whether the user may attempt a code now, without counting one: read before what a code costs
     * before it is compared (the KMS opening the secret), so that the limit bounds that cost as well.
     */
    public function mayAttempt(User $user): bool;

    /**
     * Counts one attempt of the user and tells whether it may go on.
     */
    public function attempt(User $user): bool;
}
