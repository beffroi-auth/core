<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Credential;

use DateTimeImmutable;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Tenant\Tenant;
use Symfony\Component\Uid\Uuid;

/**
 * A way for a user to prove who they are; a user has N of them.
 */
abstract class Credential
{
    /**
     * Restated from the user (decision 0002).
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

    /**
     * What the kind is called, for a console or an audit trail: "password", "passkey". Each kind names
     * itself; nothing reads a class name.
     */
    abstract public function kind(): string;

    /**
     * What the user called it, when the kind lets them name one (a passkey); null otherwise.
     */
    public function label(): ?string
    {
        return null;
    }

    public function markUsed(DateTimeImmutable $now): void
    {
        $this->lastUsedAt = $now;
    }
}
