<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\PasskeyCredential;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\LogicException;
use DateTimeImmutable;
use Webauthn\AuthenticatorData;

/**
 * WebAuthn Level 3 §6.1.1, decision 0034
 *
 * Atomic: of concurrent assertions of one passkey carrying the same counter other than zero, only one is told true. A
 * credential told true holds the use, one told false is left as it was.
 */
interface PasskeyUsesInterface
{
    /**
     * False when the counter of the assertion regresses from the one of the credential or of its row.
     *
     * @throws InvalidArgumentException when the backup state of the assertion is not one the passkey may hold
     * @throws LogicException           when the credential is revoked
     */
    public function recordUse(PasskeyCredential $credential, AuthenticatorData $data, DateTimeImmutable $now): bool;
}
