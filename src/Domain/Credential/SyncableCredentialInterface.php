<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

/**
 * WebAuthn Level 3 §6.1.3
 */
interface SyncableCredentialInterface
{
    public bool $backupEligible { get; }

    public bool $backedUp { get; }
}
