<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;

/**
 * The confirmations sent for a person and to an address, each counted on a budget of its own; the count is atomic and
 * shared by every process of a deployment.
 */
interface ConfirmationSendLimiterInterface
{
    /**
     * Counts one more confirmation for the person and one more to the address, and says whether both budgets held it.
     */
    public function spend(User $user, string $address): bool;
}
