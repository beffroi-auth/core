<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\EmailAddress;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Symfony\Component\Uid\Uuid;

interface UserRepositoryInterface
{
    public function find(Uuid $id): ?User;

    public function findBySubject(Tenant $tenant, Subject $subject): ?User;

    public function findByUsername(Tenant $tenant, string $username): ?User;

    /**
     * A person of the tenant who holds that address, its domain compared without regard to case and its
     * local part as it is written (`EmailAddress::isTheSameMailboxAs()`). Nothing says two persons may not
     * share an address, so which of them comes back when several do is not said: what asks is whether
     * somebody holds it.
     */
    public function findByEmail(Tenant $tenant, EmailAddress $email): ?User;

    public function save(User $user): void;
}
