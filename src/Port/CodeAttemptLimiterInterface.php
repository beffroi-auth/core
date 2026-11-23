<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;

/**
 * The count is atomic and shared by every process of a deployment: attempts made at once are counted one by one
 * (RFC 4226 §7.3).
 */
interface CodeAttemptLimiterInterface
{
    /**
     * Without counting one; asked before the KMS opens the secret, so that the limit bounds that cost too.
     */
    public function mayAttempt(User $user): bool;

    public function attempt(User $user): bool;
}
