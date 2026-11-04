<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;

interface CodeAttemptLimiterInterface
{
    /**
     * Without counting one; asked before the KMS opens the secret, so that the limit bounds that cost too.
     */
    public function mayAttempt(User $user): bool;

    public function attempt(User $user): bool;
}
