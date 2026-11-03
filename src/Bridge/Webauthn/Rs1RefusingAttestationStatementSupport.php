<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use Cose\Algorithm\Signature\RSA\RS1;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\AttestationStatement\AttestationStatementSupport;
use Webauthn\AuthenticatorData;

/**
 * A support of an attestation statement format that refuses a statement signed with RS1, RSASSA-PKCS1-v1_5
 * with SHA-1 (RFC 8812 §2, COSE -65535), before the library verifies it (decision 0033). The packed, TPM and
 * Android Key formats name the algorithm of their signature ("alg", WebAuthn Level 3 §8.2, §8.3, §8.4), and
 * webauthn-lib asks web-auth/cose-lib for SHA-1 without the acknowledgement: cose-lib warns, which the
 * development environment throws and production only logs, and the statement would pass there.
 */
final readonly class Rs1RefusingAttestationStatementSupport implements AttestationStatementSupport
{
    public function __construct(
        private AttestationStatementSupport $support,
    ) {
    }

    public function name(): string
    {
        return $this->support->name();
    }

    public function load(array $attestation): AttestationStatement
    {
        return $this->support->load($attestation);
    }

    public function isValid(string $clientDataJSONHash, AttestationStatement $attestationStatement, AuthenticatorData $authenticatorData): bool
    {
        // the library reads the identifier as an integer, whether CBOR carried it as one or as text
        $algorithm = $attestationStatement->has('alg') ? $attestationStatement->get('alg') : null;
        if ($algorithm === RS1::ID || $algorithm === (string) RS1::ID) {
            return false;
        }

        return $this->support->isValid($clientDataJSONHash, $attestationStatement, $authenticatorData);
    }
}
