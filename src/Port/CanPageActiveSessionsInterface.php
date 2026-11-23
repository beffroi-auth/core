<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;
use Beffroi\Core\Domain\Session\AuthenticationSession;
use Beffroi\Core\Domain\Session\SessionQuestion;
use DateTimeImmutable;

interface CanPageActiveSessionsInterface
{
    /**
     * @return Listing<AuthenticationSession> the first opened first
     */
    public function pageOfActiveSessions(User $user, DateTimeImmutable $now, SessionQuestion $question, Pagination $pagination): Listing;
}
