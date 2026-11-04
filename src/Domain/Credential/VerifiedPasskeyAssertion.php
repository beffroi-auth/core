<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Webauthn\AuthenticatorData;

final readonly class VerifiedPasskeyAssertion
{
    public function __construct(
        public PasskeyCredential $credential,
        public AuthenticatorData $authenticatorData,
    ) {
    }
}
