<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use Webauthn\Counter\CounterChecker;
use Webauthn\CredentialRecord;

/**
 * The sign count is not the library's to refuse here: the core compares it after the signature
 * (PasskeyCredential::signCountRegresses()) and refuses a regression there, with the other refusals of an
 * assertion whose signature holds, so that the refusal names the passkey and its holder (decision 0034).
 */
final class IgnoredCounterChecker implements CounterChecker
{
    public function check(CredentialRecord $credentialRecord, int $currentCounter): void
    {
    }
}
