<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Credential\PasskeyCredential;
use Beffroi\Core\Port\PasskeyUsesInterface;
use DateTimeImmutable;
use Webauthn\AuthenticatorData;

/**
 * The credential is its own row: the in-memory store hands every reader the object it holds.
 */
final readonly class InMemoryPasskeyUses implements PasskeyUsesInterface
{
    public function recordUse(PasskeyCredential $credential, AuthenticatorData $data, DateTimeImmutable $now): bool
    {
        if ($credential->signCountRegresses($data)) {
            return false;
        }
        $credential->recordUse($data, $now);

        return true;
    }
}
