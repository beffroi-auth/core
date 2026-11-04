<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use Webauthn\Counter\CounterChecker;
use Webauthn\CredentialRecord;

/**
 * The core refuses a regressed sign count after the signature (PasskeyCredential::signCountRegresses()).
 */
final class IgnoredCounterChecker implements CounterChecker
{
    public function check(CredentialRecord $credentialRecord, int $currentCounter): void
    {
    }
}
