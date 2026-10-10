<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\ClaimChange;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;

/**
 * A companion of ChangesOfAPersonInterface: one page of what that person has changed of their own record
 * (issue #482), for the panel of their profile that shows it.
 */
interface CanPageChangesOfAPersonInterface
{
    /**
     * @return Listing<ClaimChange> the most recent first
     */
    public function pageOfChanges(User $user, Pagination $pagination): Listing;
}
