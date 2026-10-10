<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Archive\PersonalArchive;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\PersonalArchiveRepositoryInterface;
use DateTimeImmutable;
use function count;
use Symfony\Component\Uid\Uuid;

/**
 * For tests and for the examples: nothing here survives the process.
 */
final class InMemoryPersonalArchiveRepository implements PersonalArchiveRepositoryInterface
{
    /**
     * @var array<string, PersonalArchive>
     */
    private array $archives = [];

    public function find(Tenant $tenant, Uuid $id): ?PersonalArchive
    {
        $archive = $this->archives[$id->toRfc4122()] ?? null;

        return $archive?->tenant->equals($tenant) === true ? $archive : null;
    }

    public function latestOf(User $user): ?PersonalArchive
    {
        $latest = null;
        foreach ($this->archives as $archive) {
            if ($archive->user->id->equals($user->id) && ($latest === null || $archive->askedAt >= $latest->askedAt)) {
                $latest = $archive;
            }
        }

        return $latest;
    }

    public function save(PersonalArchive $archive): void
    {
        $this->archives[$archive->id->toRfc4122()] = $archive;
    }

    public function forget(PersonalArchive $archive): void
    {
        unset($this->archives[$archive->id->toRfc4122()]);
    }

    public function forgetExpired(Tenant $tenant, DateTimeImmutable $moment): int
    {
        $forgotten = 0;
        foreach ($this->archives as $key => $archive) {
            if ($archive->tenant->equals($tenant) && $archive->hasExpired($moment)) {
                unset($this->archives[$key]);
                ++$forgotten;
            }
        }

        return $forgotten;
    }

    /**
     * How many archives are held, for a test that says a run of the retention forgot what it said it did.
     */
    public function count(): int
    {
        return count($this->archives);
    }
}
