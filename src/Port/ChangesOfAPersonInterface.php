<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\ClaimChange;
use Beffroi\Core\Domain\Identity\User;
use Symfony\Component\Uid\Uuid;

/**
 * What the history holds of one person (issue #482): the page of their record that shows it, and the
 * archive they ask for.
 */
interface ChangesOfAPersonInterface
{
    /**
     * @return list<ClaimChange> the most recent first
     */
    public function lastOf(User $user, int $howMany): array;

    /**
     * Every change of that person, oldest first, in pages: the reading the archive needs (issue #453),
     * where the page of their record reads the last few. The identifier is a UUIDv7, which sorts
     * chronologically, so a page is a lookup past the last change read.
     *
     * @param Uuid|null $after the last change the caller read, null for the first page
     *
     * @return list<ClaimChange> the oldest first
     */
    public function everyChangeOf(User $user, int $howMany, ?Uuid $after = null): array;
}
