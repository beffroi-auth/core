<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Credential;

use DateTimeImmutable;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Tenant\Tenant;
use Symfony\Component\Uid\Uuid;

/**
 * A way for a user to prove who they are. A user has N of them; the password is one kind, passkeys and TOTP
 * are others to come.
 */
abstract class Credential
{
    /**
     * The tenant of the user, restated so that every stored credential carries it (decision 0002).
     */
    public readonly Tenant $tenant;

    public private(set) ?DateTimeImmutable $lastUsedAt = null;

    public function __construct(
        public private(set) Uuid $id,
        public readonly User $user,
        public readonly DateTimeImmutable $createdAt,
    ) {
        $this->tenant = $user->tenant;
    }

    public function markUsed(DateTimeImmutable $now): void
    {
        $this->lastUsedAt = $now;
    }
}
