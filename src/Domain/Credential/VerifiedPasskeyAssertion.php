<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Credential;

use Webauthn\AuthenticatorData;

/**
 * What the authentication ceremony (WebAuthn Level 3 §7.2) established: which passkey of the tenant
 * signed, and the authenticator data it signed, whose flags say what was proven.
 */
final readonly class VerifiedPasskeyAssertion
{
    public function __construct(
        public PasskeyCredential $credential,
        public AuthenticatorData $authenticatorData,
    ) {
    }
}
