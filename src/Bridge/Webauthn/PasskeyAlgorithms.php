<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use Cose\Algorithm\Manager;
use Psr\Clock\ClockInterface;
use Webauthn\AttestationStatement\AttestationStatementSupport;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\PublicKeyCredentialParameters;

interface PasskeyAlgorithms
{
    /**
     * @return non-empty-list<PublicKeyCredentialParameters> in the order of preference
     */
    public function parameters(): array;

    /**
     * The algorithms the options offer, and none other.
     */
    public function verifiers(): Manager;

    /**
     * WebAuthn Level 3 §8.3
     */
    public function tpm(ClockInterface $clock): TPMAttestationStatementSupport;

    /**
     * The support as the library gives it, or refusing what the library would verify only with a warning.
     */
    public function attestation(AttestationStatementSupport $support): AttestationStatementSupport;
}
