<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Credential;

use DateTimeImmutable;
use Heidi\Core\Domain\Identity\User;
use Symfony\Component\Uid\Uuid;

/**
 * A way for a user to prove who they are. A user has N of them; the password is one kind, passkeys and TOTP
 * are others to come.
 */
abstract class Credential
{
    public private(set) ?DateTimeImmutable $lastUsedAt = null;

    public function __construct(
        public readonly Uuid $id,
        public readonly User $user,
        public readonly DateTimeImmutable $createdAt,
    ) {
    }

    public function markUsed(DateTimeImmutable $now): void
    {
        $this->lastUsedAt = $now;
    }
}
