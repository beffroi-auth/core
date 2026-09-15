<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\InMemory;

use Heidi\Core\Domain\Credential\Credential;
use Heidi\Core\Domain\Credential\PasswordCredential;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Port\CredentialRepositoryInterface;

final class InMemoryCredentialRepository implements CredentialRepositoryInterface
{
    /**
     * @var array<string, Credential>
     */
    private array $credentials = [];

    public function findByUser(User $user): array
    {
        $found = [];
        foreach ($this->credentials as $credential) {
            if ($credential->user->id->equals($user->id)) {
                $found[] = $credential;
            }
        }

        return $found;
    }

    public function findPasswordCredential(User $user): ?PasswordCredential
    {
        foreach ($this->findByUser($user) as $credential) {
            if ($credential instanceof PasswordCredential) {
                return $credential;
            }
        }

        return null;
    }

    public function save(Credential $credential): void
    {
        $this->credentials[$credential->id->toRfc4122()] = $credential;
    }
}
