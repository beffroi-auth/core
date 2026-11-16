<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential\Attestation;

use Beffroi\Core\Domain\Tenant\PasskeyAttestation;
use Beffroi\Core\Exception\InvalidArgumentException;
use DateTimeImmutable;
use Webauthn\PublicKeyCredentialCreationOptions;

/**
 * WebAuthn Level 3 §5.4.7 "none": the model of a passkey is the one its authenticator declares.
 */
final readonly class NoAttestation implements PasskeyAttestationPolicyInterface
{
    public function name(): string
    {
        return PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE;
    }

    public function conveyancePreference(): string
    {
        return PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE;
    }

    public function assertReads(PasskeyAttestation $attestation): void
    {
        if ($attestation->roots !== [] || $attestation->ownRoots !== []) {
            throw new InvalidArgumentException('The passkey attestation policy "none" verifies no chain: only "enterprise" reads roots of attestations.');
        }
        if ($attestation->modelsUnderAlert !== []) {
            throw new InvalidArgumentException('The passkey attestation policy "none" proves no model, so it refuses none under a security alert and admits none either.');
        }
    }

    public function judge(PasskeyAttestation $attestation, AttestationInterface $seen, DateTimeImmutable $now): AttestationEvidence
    {
        return AttestationEvidence::declared();
    }
}
