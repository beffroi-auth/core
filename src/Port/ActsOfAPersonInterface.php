<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Journal\JournalEntry;
use Symfony\Component\Uid\Uuid;

interface ActsOfAPersonInterface
{
    /**
     * @param list<string> $acts the names of the acts, as the events give them
     *
     * @return list<JournalEntry> the most recent first
     */
    public function lastOf(User $user, array $acts, int $howMany): array;

    /**
     * @param Uuid|null $after the last act the caller read, null for the first page
     *
     * @return list<JournalEntry> the oldest first
     */
    public function everyActOf(User $user, int $howMany, ?Uuid $after = null): array;
}
