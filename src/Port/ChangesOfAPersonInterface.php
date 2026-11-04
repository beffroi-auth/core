<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\ClaimChange;
use Beffroi\Core\Domain\Identity\User;
use Symfony\Component\Uid\Uuid;

interface ChangesOfAPersonInterface
{
    /**
     * @return list<ClaimChange> the most recent first
     */
    public function lastOf(User $user, int $howMany): array;

    /**
     * @param Uuid|null $after the last change the caller read, null for the first page
     *
     * @return list<ClaimChange> the oldest first
     */
    public function everyChangeOf(User $user, int $howMany, ?Uuid $after = null): array;
}
