<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\InMemory;

use DateTimeImmutable;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Session\AuthenticationSession;
use Heidi\Core\Port\AuthenticationSessionRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class InMemoryAuthenticationSessionRepository implements AuthenticationSessionRepositoryInterface
{
    /**
     * @var array<string, AuthenticationSession>
     */
    private array $sessions = [];

    /**
     * For tests that must see a write happen on an object they already hold.
     */
    public private(set) int $saves = 0;

    public function find(Uuid $id): ?AuthenticationSession
    {
        return $this->sessions[$id->toRfc4122()] ?? null;
    }

    public function findActiveByUser(User $user, DateTimeImmutable $now): array
    {
        $active = [];
        foreach ($this->sessions as $session) {
            if ($session->user->id->equals($user->id) && $session->isActive($now)) {
                $active[] = $session;
            }
        }

        return $active;
    }

    public function save(AuthenticationSession $session): void
    {
        ++$this->saves;
        $this->sessions[$session->id->toRfc4122()] = $session;
    }
}
