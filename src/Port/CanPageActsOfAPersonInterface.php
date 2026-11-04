<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Journal\JournalEntry;
use Beffroi\Core\Domain\Journal\JournalQuestion;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;

interface CanPageActsOfAPersonInterface
{
    /**
     * @return Listing<JournalEntry> the most recent first
     */
    public function pageOfActs(User $user, JournalQuestion $question, Pagination $pagination): Listing;
}
