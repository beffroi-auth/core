<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Exception\LogicException;
use DateTimeImmutable;

/**
 * RFC 6238 §5.2
 *
 * Atomic: of concurrent requests presenting the same code, exactly one is told true. A credential told true holds the
 * use, one told false is left as it was.
 */
interface TotpUsesInterface
{
    /**
     * False when the credential or its row holds this step or a later one.
     *
     * @throws LogicException when the credential is revoked
     */
    public function acceptStep(TotpCredential $credential, int $step, DateTimeImmutable $now): bool;

    /**
     * False when the credential holds no unspent backup code at that position, or when its row no longer holds the
     * backup codes the credential was read with.
     *
     * @throws LogicException when the credential is revoked
     */
    public function spendBackupCode(TotpCredential $credential, int $index, DateTimeImmutable $now): bool;
}
