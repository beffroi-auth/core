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

    /**
     * Replaces the hash after a verification with a stronger algorithm or parameters. The password itself
     * did not change, so this is not a password change for the user.
     */
    public function rehash(PasswordHash $hash, DateTimeImmutable $now): void
    {
        $this->hash = $hash;
        $this->hashChangedAt = $now;
    }

    /**
     * Another password: what verified the previous one verifies nothing any more. Whether the sessions opened
     * with the previous password survive is the caller's policy, not the credential's.
     */
    public function changePassword(PasswordHash $hash, DateTimeImmutable $now): void
    {
        $this->hash = $hash;
        $this->hashChangedAt = $now;
    }
}
