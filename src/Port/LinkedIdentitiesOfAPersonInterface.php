<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\LinkedIdentity;
use Beffroi\Core\Domain\Identity\User;

/**
 * A companion of LinkedIdentityRepositoryInterface: the identities a person has at upstream providers, for
 * the archive they ask for (issue #453). A federated login recognises one identity by (provider, subject),
 * which is what that port answers; this answers the question a person asks of their own record.
 */
interface LinkedIdentitiesOfAPersonInterface
{
    /**
     * @return list<LinkedIdentity> the most recently linked first
     */
    public function of(User $user): array;
}
