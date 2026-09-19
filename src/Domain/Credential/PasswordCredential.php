<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Credential;

use DateTimeImmutable;
use Heidi\Core\Domain\Identity\User;
use Symfony\Component\Uid\Uuid;

final class PasswordCredential extends Credential
{
    public private(set) DateTimeImmutable $hashChangedAt;

    public function __construct(
        Uuid $id,
        User $user,
        public private(set) PasswordHash $hash,
        DateTimeImmutable $createdAt,
    ) {
        parent::__construct($id, $user, $createdAt);
        $this->hashChangedAt = $createdAt;
    }

    public function kind(): string
    {
        return 'password';
    }

    /**
     * A stronger hash of the same password.
     */
    public function rehash(PasswordHash $hash, DateTimeImmutable $now): void
    {
        $this->hash = $hash;
        $this->hashChangedAt = $now;
    }

    /**
     * Another password; what happens to the sessions is the caller's policy.
     */
    public function changePassword(PasswordHash $hash, DateTimeImmutable $now): void
    {
        $this->hash = $hash;
        $this->hashChangedAt = $now;
    }
}
