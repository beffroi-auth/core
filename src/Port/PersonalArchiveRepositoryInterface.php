<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Archive\PersonalArchive;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

interface PersonalArchiveRepositoryInterface
{
    public function find(Tenant $tenant, Uuid $id): ?PersonalArchive;

    public function latestOf(User $user): ?PersonalArchive;

    public function save(PersonalArchive $archive): void;

    /**
     * The row goes and leaves nothing behind: an archive holds every sealed column in the clear.
     */
    public function forget(PersonalArchive $archive): void;

    /**
     * Bounded by the implementation: a backlog is worked through over several runs.
     */
    public function forgetExpired(Tenant $tenant, DateTimeImmutable $moment): int;
}
