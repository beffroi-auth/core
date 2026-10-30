<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationSession;

/**
 * A companion of AuthenticationSessionRepositoryInterface: the sessions of one person that nothing has ended, those
 * past their expiry included, for what ends every session of a person but the one it keeps (OtherSessionsTermination).
 * Reaching its expiry ends no session (decision 0031): a session that expired signs nobody in, and what it issued
 * lives on until something ends it.
 */
interface CanFindUnendedSessionsInterface
{
    /**
     * @return list<AuthenticationSession> in the order they were opened
     */
    public function findUnendedByUser(User $user): array;
}
