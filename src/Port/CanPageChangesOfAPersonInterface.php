<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\ClaimChange;
use Beffroi\Core\Domain\Identity\ClaimChangeQuestion;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;

interface CanPageChangesOfAPersonInterface
{
    /**
     * @return Listing<ClaimChange> the most recent first
     */
    public function pageOfChanges(User $user, ClaimChangeQuestion $question, Pagination $pagination): Listing;

    /**
     * @return list<string> the claims a change of the person is about, each once, in the order of their names
     */
    public function claimsChangedOf(User $user): array;
}
