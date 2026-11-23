<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use Cose\Algorithm\Signature\RSA\RS1;
use function is_float;
use function is_int;
use function is_string;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\AttestationStatement\AttestationStatementSupport;
use Webauthn\AuthenticatorData;

/**
 * RFC 8812 §2, WebAuthn Level 3 §8.2, §8.3, §8.4
 *
 * webauthn-lib asks cose-lib for SHA-1 without the acknowledgement, and cose-lib only warns, which production
 * merely logs: the statement would pass there.
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
        // Read as the library reads it, by a cast: a CBOR integer arrives as text, and a crafted text or float as well.
        $algorithm = $attestationStatement->has('alg') ? $attestationStatement->get('alg') : null;
        if ((is_int($algorithm) || is_float($algorithm) || is_string($algorithm)) && (int) $algorithm === RS1::ID) {
            return false;
        }

        return $this->support->isValid($clientDataJSONHash, $attestationStatement, $authenticatorData);
    }
}
