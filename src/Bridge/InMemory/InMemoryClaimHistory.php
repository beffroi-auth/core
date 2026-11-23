<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Identity\ClaimChange;
use Beffroi\Core\Domain\Identity\ClaimChangeQuestion;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;
use Beffroi\Core\Port\CanPageChangesOfAPersonInterface;
use Beffroi\Core\Port\ChangesOfAPersonInterface;
use Beffroi\Core\Port\ClaimHistoryInterface;
use function array_filter;
use function array_map;
use function array_slice;
use function array_unique;
use function array_values;
use function sort;
use Symfony\Component\Uid\Uuid;
use function usort;

final class InMemoryClaimHistory implements CanPageChangesOfAPersonInterface, ChangesOfAPersonInterface, ClaimHistoryInterface
{
    /**
     * @var array<string, ClaimChange>
     */
    private array $changes = [];

    /**
     * @return Listing<ClaimChange>
     */
    public function pageOfChanges(User $user, ClaimChangeQuestion $question, Pagination $pagination): Listing
    {
        $theirs = array_values(array_filter($this->ofTheirs($user), $question->admits(...)));
        usort($theirs, static fn (ClaimChange $a, ClaimChange $b): int => $b->id->compare($a->id));

        return Listing::of($theirs, $pagination);
    }

    public function claimsChangedOf(User $user): array
    {
        $claims = array_values(array_unique(array_map(static fn (ClaimChange $change): string => $change->claim, $this->ofTheirs($user))));
        sort($claims);

        return $claims;
    }

    public function record(ClaimChange $change): void
    {
        $this->changes[$change->id->toRfc4122()] = $change;
    }

    public function lastOf(User $user, int $howMany): array
    {
        if ($howMany < 1) {
            return [];
        }
        $theirs = $this->ofTheirs($user);
        usort($theirs, static fn (ClaimChange $a, ClaimChange $b): int => $b->id->compare($a->id));

        return array_slice($theirs, 0, $howMany);
    }

    public function everyChangeOf(User $user, int $howMany, ?Uuid $after = null): array
    {
        if ($howMany < 1) {
            return [];
        }
        $found = [];
        foreach ($this->ofTheirs($user) as $change) {
            if ($after === null || $change->id->compare($after) > 0) {
                $found[] = $change;
            }
        }
        usort($found, static fn (ClaimChange $a, ClaimChange $b): int => $a->id->compare($b->id));

        return array_slice($found, 0, $howMany);
    }

    /**
     * @return list<ClaimChange>
     */
    private function ofTheirs(User $user): array
    {
        $theirs = [];
        foreach ($this->changes as $change) {
            if ($change->user->id->equals($user->id)) {
                $theirs[] = $change;
            }
        }

        return $theirs;
    }
}
