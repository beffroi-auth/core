<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential\Attestation;

use Beffroi\Core\Domain\Tenant\PasskeyAttestation;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\PasskeyAttestationRefusedException;
use DateTimeImmutable;
use Webauthn\PublicKeyCredentialCreationOptions;

/**
 * WebAuthn Level 3 §5.4.7 "enterprise", §8.2.2: only an attestation that chains up to a root the tenant pins enrols, and
 * under a root that is no authority of the tenant itself it carries the serial number of its authenticator, which no
 * attestation of a whole range of a maker carries.
 */
final readonly class EnterpriseAttestation implements PasskeyAttestationPolicyInterface
{
    public function name(): string
    {
        return PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_ENTERPRISE;
    }

    public function conveyancePreference(): string
    {
        return PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_ENTERPRISE;
    }

    public function assertReads(PasskeyAttestation $attestation): void
    {
        if ($attestation->roots === [] && $attestation->ownRoots === []) {
            throw new InvalidArgumentException('The passkey attestation policy "enterprise" admits the attestations of the roots the tenant pins, and it pins none.');
        }
    }

    public function judge(PasskeyAttestation $attestation, AttestationInterface $seen, DateTimeImmutable $now): AttestationEvidence
    {
        if ($seen->chainsUpTo($attestation->ownRoots)) {
            return AttestationEvidence::enterprise($seen->serialNumber(), $attestation->admitsTheAlertOf($seen, $now));
        }
        if (! $seen->chainsUpTo($attestation->roots)) {
            throw new PasskeyAttestationRefusedException(
                PasskeyAttestationRefusal::OrganisationKeysOnly,
                'The attestation chains up to no root the tenant pins.',
            );
        }

        return AttestationEvidence::enterprise(
            $seen->serialNumber() ?? throw new PasskeyAttestationRefusedException(
                PasskeyAttestationRefusal::OrganisationKeysOnly,
                'The attestation chains up to a root of enterprise attestations and carries no serial number (WebAuthn Level 3 §8.2.2).',
            ),
            $attestation->admitsTheAlertOf($seen, $now),
        );
    }
}
