<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;
use Beffroi\Core\Domain\Listing\Period;

/**
 * A companion of CredentialRepositoryInterface: one page of the credentials of one kind a person holds, for
 * the table of their account that shows them (architecture §5.2). The kind is a class, as it is there: a
 * page of their passkeys is read by the page of their passkeys and names no other kind.
 */
interface CanPageCredentialsInterface
{
    /**
     * @param class-string<Credential> $kind
     * @param Period|null $period the stretch of time they were enrolled over, all of them when null
     *
     * @return Listing<Credential> the most recently enrolled first
     */
    public function pageOfCredentials(User $user, string $kind, Pagination $pagination, ?Period $period = null): Listing;
}
