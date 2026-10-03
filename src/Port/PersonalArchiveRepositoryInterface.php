<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Archive\PersonalArchive;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * Where the archives a person asked for are kept (issue #453).
 *
 * One port and not two, where the journal has one for its writes and one for the page that reads it: every
 * use case of an archive both reads one and writes one, the build included, so a caller that saw only half
 * of this would be a caller that does not exist.
 */
interface PersonalArchiveRepositoryInterface
{
    /**
     * The tenant is named beside the identifier because an archive is asked for by the person of one: an
     * identifier that belongs to another tenant is nothing here, as it is everywhere else.
     */
    public function find(Tenant $tenant, Uuid $id): ?PersonalArchive;

    /**
     * The last archive that person asked for, whatever state it is in, null when they never asked. The one
     * reading the pages of the account need: what a person is offered is their own last archive, so there
     * is no identifier to guess and none to compare.
     */
    public function latestOf(User $user): ?PersonalArchive;

    public function save(PersonalArchive $archive): void;

    /**
     * Forgets the archives of this tenant that expired before the moment given, and says how many it
     * forgot. Bounded by the implementation, so a backlog is worked through over several runs.
     */
    public function forgetExpired(Tenant $tenant, DateTimeImmutable $moment): int;
}
