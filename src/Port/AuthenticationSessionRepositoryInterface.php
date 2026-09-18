<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use DateTimeImmutable;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Session\AuthenticationSession;
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
