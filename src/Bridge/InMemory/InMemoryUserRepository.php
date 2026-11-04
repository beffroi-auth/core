<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Identity\EmailAddress;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\UserRepositoryInterface;
use Symfony\Component\Uid\Uuid;

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

    public function findAllByEmail(Tenant $tenant, EmailAddress $email): array
    {
        $holders = [];
        foreach ($this->users as $user) {
            if ($user->belongsTo($tenant) && $user->email?->isTheSameMailboxAs($email) === true) {
                $holders[] = $user;
            }
        }

        return $holders;
    }

    public function save(User $user): void
    {
        $this->users[$user->id->toRfc4122()] = $user;
    }
}
