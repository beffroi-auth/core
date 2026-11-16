<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential\Attestation;

use Beffroi\Core\Domain\Tenant\PasskeyAttestation;
use Beffroi\Core\Exception\InvalidArgumentException;
use DateTimeImmutable;
use Webauthn\PublicKeyCredentialCreationOptions;

/**
 * WebAuthn Level 3 §5.4.7 "direct": the model is verified when the FIDO metadata know it, declared otherwise, and a passkey is
 * never refused for want of an attestation, the client being free to send none.
 */
final readonly class DirectAttestation implements PasskeyAttestationPolicyInterface
{
    public function name(): string
    {
        return PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_DIRECT;
    }

    public function conveyancePreference(): string
    {
        return PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_DIRECT;
    }

    public function assertReads(PasskeyAttestation $attestation): void
    {
        if ($attestation->roots !== [] || $attestation->ownRoots !== []) {
            throw new InvalidArgumentException('The passkey attestation policy "direct" verifies a chain up to the roots the FIDO metadata name: only "enterprise" reads roots of the tenant.');
        }
    }

    public function judge(PasskeyAttestation $attestation, AttestationInterface $seen, DateTimeImmutable $now): AttestationEvidence
    {
        $model = $seen->provesTheModel() ? ModelProof::Verified : ModelProof::Declared;

        return new AttestationEvidence($model, underSecurityAlert: $attestation->admitsTheAlertOf($seen, $now));
    }
}
