<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use DateTimeImmutable;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationSession;
use Symfony\Component\Uid\Uuid;

interface AuthenticationSessionRepositoryInterface
{
    public function find(Uuid $id): ?AuthenticationSession;

    /**
     * @return list<AuthenticationSession>
     */
    public function findActiveByUser(User $user, DateTimeImmutable $now): array;

    public function save(AuthenticationSession $session): void;
}
