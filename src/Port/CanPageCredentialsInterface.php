<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;
use Beffroi\Core\Domain\Listing\Period;

interface CanPageCredentialsInterface
{
    /**
     * @param class-string<Credential> $kind
     * @param Period|null $period when they were enrolled
     *
     * @return Listing<Credential> the most recently enrolled first
     */
    public function pageOfCredentials(User $user, string $kind, Pagination $pagination, ?Period $period = null): Listing;
}
