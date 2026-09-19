<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Credential\PasskeyCredential;
use Heidi\Core\Domain\Credential\VerifiedPasskeyAssertion;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\PasskeyCeremonyFailedException;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * The authentication ceremony of WebAuthn Level 3 §7.2, run by the integration over webauthn-lib: the
 * signature, the origin, the challenge and the relying party are its business; what the assertion proves,
 * the sign count and the revocation are the core's.
 */
interface PasskeyAuthenticatorInterface
{
    /**
     * The options the browser asserts with (§5.5): the passkeys of a user who named themself, none for a
     * discoverable credential (§6.3.2.1, the authenticator picks); the challenge is the implementation's.
     *
     * @param list<PasskeyCredential> $allowed
     */
    public function requestOptions(Tenant $tenant, array $allowed): PublicKeyCredentialRequestOptions;

    /**
     * The response of the browser, as it serialised it: the passkey of the tenant it names, found through
     * CredentialRepositoryInterface::findByExternalId(), and its authenticator data once the signature holds.
     *
     * @throws PasskeyCeremonyFailedException
     */
    public function verifyAssertion(Tenant $tenant, PublicKeyCredentialRequestOptions $options, string $response): VerifiedPasskeyAssertion;
}
