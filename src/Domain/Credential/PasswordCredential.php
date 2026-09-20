<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Credential;

use DateTimeImmutable;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Session\AuthenticationMethod;
use Symfony\Component\Uid\Uuid;

/**
 * A password: what a user knows, "pwd" (RFC 8176 §2). Replaced, never revoked.
 */
final class PasswordCredential extends Credential implements ProvingCredentialInterface
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

    public function provableMethods(): array
    {
        return [AuthenticationMethod::password()];
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
