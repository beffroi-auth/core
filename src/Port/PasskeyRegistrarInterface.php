<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\PasskeyCredential;
use Beffroi\Core\Domain\Credential\PasskeyUserHandle;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\PasskeyCeremonyFailedException;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialCreationOptions;

/**
 * The registration ceremony of WebAuthn Level 3 §7.1, run by the integration over webauthn-lib: the core
 * says for whom and with which handle, and reads the record that comes out; it parses nothing.
 */
interface PasskeyRegistrarInterface
{
    /**
     * The options the browser creates a passkey with (§5.4): the relying party is the tenant, the user
     * entity carries the handle given, the passkeys listed are excluded so that the authenticator does not
     * enrol twice (§5.4, excludeCredentials); the challenge is the implementation's to draw and to keep.
     *
     * @param list<PasskeyCredential> $existing
     */
    public function creationOptions(User $user, PasskeyUserHandle $userHandle, array $existing): PublicKeyCredentialCreationOptions;

    /**
     * The response of the browser, as it serialised it, checked against the options: the origin of the
     * issuer of the tenant, which the options do not carry, the challenge, the relying party, and the
     * attestation when the authenticator sent one, never required.
     *
     * @throws PasskeyCeremonyFailedException
     */
    public function verifyAttestation(Tenant $tenant, PublicKeyCredentialCreationOptions $options, string $response): CredentialRecord;
}
