<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationSession;

/**
 * Sessions past their expiry included: reaching its expiry ends no session.
 */
interface CanFindUnendedSessionsInterface
{
    /**
     * @return list<AuthenticationSession>
     */
    public function findUnendedByUser(User $user): array;
}
