<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\Attestation\VerifiedRegistration;
use Beffroi\Core\Domain\Credential\PasskeyCredential;
use Beffroi\Core\Domain\Credential\PasskeyUserHandle;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\PasskeyAttestationRefusedException;
use Beffroi\Core\Exception\PasskeyCeremonyFailedException;
use Webauthn\PublicKeyCredentialCreationOptions;

/**
 * WebAuthn Level 3 §7.1
 */
interface PasskeyRegistrarInterface
{
    /**
     * WebAuthn Level 3 §5.4
     *
     * @param list<PasskeyCredential> $existing the excludeCredentials
     */
    public function creationOptions(User $user, PasskeyUserHandle $userHandle, array $existing): PublicKeyCredentialCreationOptions;

    /**
     * The origin is the issuer's, which the options do not carry.
     *
     * @throws PasskeyCeremonyFailedException
     * @throws PasskeyAttestationRefusedException
     */
    public function verifyAttestation(Tenant $tenant, PublicKeyCredentialCreationOptions $options, string $response): VerifiedRegistration;
}
