<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Identity\User;

/**
 * How many passwords may be tried on a user in a window: a password is guessed online one attempt at a
 * time, so the attempts on an account are limited before anything is verified (decision 0006), by the
 * user and never by the address, because a provider serves whole organisations behind one. What the
 * window is and how it is kept is the implementation's (the rate limiter of Symfony in the bundle); the
 * rule that there is one, and that a correct password empties the count, is the core's. An unknown
 * username names no user and is counted nowhere: a limiter keyed by what was typed would fill with
 * whatever an attacker types, and the decoy hash already makes an unknown user cost a wrong password.
 */
interface PasswordAttemptLimiterInterface
{
    /**
     * Counts one attempt on the user and tells whether it may go on.
     */
    public function attempt(User $user): bool;

    /**
     * Forgets the attempts on the user: the password was right, whoever tried before did not have it.
     */
    public function reset(User $user): void;
}
