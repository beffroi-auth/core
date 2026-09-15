<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Identity\Subject;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Tenant\Tenant;
use Symfony\Component\Uid\Uuid;

interface UserRepositoryInterface
{
    public function find(Uuid $id): ?User;

    public function findBySubject(Tenant $tenant, Subject $subject): ?User;

    public function findByUsername(Tenant $tenant, string $username): ?User;

    public function save(User $user): void;
}
