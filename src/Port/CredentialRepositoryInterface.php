<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Credential\Credential;
use Heidi\Core\Domain\Credential\PasswordCredential;
use Heidi\Core\Domain\Identity\User;

interface CredentialRepositoryInterface
{
    /**
     * @return list<Credential>
     */
    public function findByUser(User $user): array;

    public function findPasswordCredential(User $user): ?PasswordCredential;

    public function save(Credential $credential): void;
}
