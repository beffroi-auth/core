<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Credential;

use DateTimeImmutable;

/**
 * A credential that can be taken out of use without leaving the row: a passkey, a TOTP secret. A password
 * is not one, it is replaced.
 */
interface RevocableCredentialInterface
{
    public ?DateTimeImmutable $revokedAt { get; }

    /**
     * The first revocation wins.
     */
    public function revoke(DateTimeImmutable $now): void;

    public function isRevoked(): bool;
}
