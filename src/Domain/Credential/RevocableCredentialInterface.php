<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use DateTimeImmutable;

interface RevocableCredentialInterface
{
    public ?DateTimeImmutable $revokedAt { get; }

    /**
     * The first revocation wins.
     */
    public function revoke(DateTimeImmutable $now): void;

    public function isRevoked(): bool;
}
