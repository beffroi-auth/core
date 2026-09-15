<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\InMemory;

use Heidi\Core\Domain\Identity\Subject;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Port\UserRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * For tests and for the examples: nothing here survives the process.
 */
final class InMemoryUserRepository implements UserRepositoryInterface
{
    /**
     * @var array<string, User>
     */
    private array $users = [];

    public function find(Uuid $id): ?User
    {
        return $this->users[$id->toRfc4122()] ?? null;
    }

    public function findBySubject(Tenant $tenant, Subject $subject): ?User
    {
        foreach ($this->users as $user) {
            if ($user->belongsTo($tenant) && $user->subject->equals($subject)) {
                return $user;
            }
        }

        return null;
    }

    public function findByUsername(Tenant $tenant, string $username): ?User
    {
        foreach ($this->users as $user) {
            if ($user->belongsTo($tenant) && $user->username === $username) {
                return $user;
            }
        }

        return null;
    }

    public function save(User $user): void
    {
        $this->users[$user->id->toRfc4122()] = $user;
    }
}
