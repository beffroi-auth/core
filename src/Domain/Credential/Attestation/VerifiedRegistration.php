<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential\Attestation;

use Webauthn\CredentialRecord;

/**
 * WebAuthn Level 3 §7.1 step 26
 */
final readonly class VerifiedRegistration
{
    public function __construct(
        public CredentialRecord $record,
        public AttestationEvidence $evidence,
    ) {
    }
}
