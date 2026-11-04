<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\LinkedIdentity;
use Beffroi\Core\Domain\Identity\User;

interface LinkedIdentitiesOfAPersonInterface
{
    /**
     * @return list<LinkedIdentity> the most recently linked first
     */
    public function of(User $user): array;
}
