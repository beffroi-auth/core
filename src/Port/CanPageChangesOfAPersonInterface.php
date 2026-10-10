<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\ClaimChange;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;
use Beffroi\Core\Domain\Listing\Period;

/**
 * A companion of ChangesOfAPersonInterface: one page of what that person has changed of their own record
 * (issue #482), for the page of their account that reads it.
 *
 * The page is taken of the stretch of time that was asked for and not of the whole: a history grows without
 * end, so what a reader narrows is read before it is paged, and the count a control shows is the count of
 * what they asked for.
 */
interface CanPageChangesOfAPersonInterface
{
    /**
     * @param Period|null $period the stretch of time to read, the whole of the history when null
     *
     * @return Listing<ClaimChange> the most recent first
     */
    public function pageOfChanges(User $user, Pagination $pagination, ?Period $period = null): Listing;
}
