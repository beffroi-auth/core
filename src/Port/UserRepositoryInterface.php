<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Symfony\Component\Uid\Uuid;

interface UserRepositoryInterface
{
    public function find(Uuid $id): ?User;

    public function findBySubject(Tenant $tenant, Subject $subject): ?User;

    public function findByUsername(Tenant $tenant, string $username): ?User;

    public function save(User $user): void;
}
