<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;
use Beffroi\Core\Domain\Listing\Period;
use Beffroi\Core\Domain\Session\AuthenticationSession;
use DateTimeImmutable;

/**
 * A companion of AuthenticationSessionRepositoryInterface: one page of the sessions open in the name of one
 * person, for the table of their account that ends one of them (issue #450).
 */
interface CanPageActiveSessionsInterface
{
    /**
     * @param Period|null $period the stretch of time the sessions were opened over, all of them when null
     *
     * @return Listing<AuthenticationSession>
     */
    public function pageOfActiveSessions(User $user, DateTimeImmutable $now, Pagination $pagination, ?Period $period = null): Listing;
}
