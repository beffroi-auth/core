<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use Webauthn\Counter\CounterChecker;
use Webauthn\CredentialRecord;

/**
 * The sign count is not the library's to refuse here: the core compares it after the signature
 * (PasskeyCredential::signCountRegresses()) and tells a regression as an event, because a synced passkey
 * keeps it at zero (architecture §3.2).
 */
final class IgnoredCounterChecker implements CounterChecker
{
    public function check(CredentialRecord $credentialRecord, int $currentCounter): void
    {
    }
}
