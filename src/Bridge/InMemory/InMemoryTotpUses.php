<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Port\TotpUsesInterface;
use DateTimeImmutable;

/**
 * The credential is its own row: the in-memory store hands every reader the object it holds.
 */
final readonly class InMemoryTotpUses implements TotpUsesInterface
{
    public function acceptStep(TotpCredential $credential, int $step, DateTimeImmutable $now): bool
    {
        if (! $credential->acceptsStep($step)) {
            return false;
        }
        $credential->recordUse($step, $now);

        return true;
    }

    public function spendBackupCode(TotpCredential $credential, int $index, DateTimeImmutable $now): bool
    {
        if (! $credential->holdsUnspentBackupCode($index)) {
            return false;
        }
        $credential->spendBackupCode($index, $now);

        return true;
    }
}
