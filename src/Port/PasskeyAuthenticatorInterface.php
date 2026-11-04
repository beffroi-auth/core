<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\PasskeyCredential;
use Beffroi\Core\Domain\Credential\VerifiedPasskeyAssertion;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\PasskeyCeremonyFailedException;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * WebAuthn Level 3 §7.2
 *
 * The implementation verifies the signature, the origin, the challenge and the relying party.
 */
interface PasskeyAuthenticatorInterface
{
    /**
     * WebAuthn Level 3 §5.5
     *
     * @param list<PasskeyCredential> $allowed empty for a discoverable credential
     */
    public function requestOptions(Tenant $tenant, array $allowed): PublicKeyCredentialRequestOptions;

    /**
     * @throws PasskeyCeremonyFailedException
     */
    public function verifyAssertion(Tenant $tenant, PublicKeyCredentialRequestOptions $options, string $response): VerifiedPasskeyAssertion;
}
