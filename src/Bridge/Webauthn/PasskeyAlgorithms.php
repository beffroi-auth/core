<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use Cose\Algorithm\Manager;
use Psr\Clock\ClockInterface;
use Webauthn\AttestationStatement\AttestationStatementSupport;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\PublicKeyCredentialParameters;

/**
 * The algorithms a set of ceremonies offers and verifies (decision 0033): those of the provider, and those
 * of the FIDO2 server API of the conformance tools, the one place where what signs over SHA-1 is verified,
 * its weakness acknowledged by name. A class per set; WebauthnCeremonies reads nothing else of it.
 */
interface PasskeyAlgorithms
{
    /**
     * What the options of a creation ceremony offer, in the order of preference (WebAuthn Level 3 §5.4,
     * pubKeyCredParams); CheckAlgorithm refuses a credential of any other.
     *
     * @return non-empty-list<PublicKeyCredentialParameters>
     */
    public function parameters(): array;

    /**
     * What the signature of a self attestation and of an assertion is verified with: the algorithms the
     * options offer, and none other.
     */
    public function verifiers(): Manager;

    /**
     * The support of the TPM attestation statement format (WebAuthn Level 3 §8.3), whose algorithm the
     * authenticator picks, RS1 included.
     */
    public function tpm(ClockInterface $clock): TPMAttestationStatementSupport;

    /**
     * A support of an attestation statement format as the ceremonies run it: as the library gives it, or
     * refusing what the library would verify only with a warning.
     */
    public function attestation(AttestationStatementSupport $support): AttestationStatementSupport;
}
