<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use function array_map;
use Cose\Algorithm\Algorithm;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\ECDSA\ES384;
use Cose\Algorithm\Signature\EdDSA\Ed25519;
use Cose\Algorithm\Signature\RSA\RS256;
use Psr\Clock\ClockInterface;
use Webauthn\AttestationStatement\AttestationStatementSupport;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\PublicKeyCredentialParameters;

/**
 * The algorithms of the provider (decision 0033): ES384, EdDSA over Ed25519, ES256 and RS256 (RFC 9053 §2.1
 * and §2.2, RFC 8812 §2), from the strongest to the weakest as w3c/webauthn#2448 proposes; the options
 * offer them in that order and the signatures are verified with them alone. What signs over SHA-1 is
 * neither offered nor verified: an attestation statement signed with RS1 is refused before the library
 * reads it, whatever its format.
 */
final readonly class ProviderPasskeyAlgorithms implements PasskeyAlgorithms
{
    public function parameters(): array
    {
        return array_map(
            static fn (Algorithm $algorithm): PublicKeyCredentialParameters => PublicKeyCredentialParameters::createPk($algorithm::identifier()),
            self::algorithms(),
        );
    }

    public function verifiers(): Manager
    {
        return Manager::create()->add(...self::algorithms());
    }

    public function tpm(ClockInterface $clock): TPMAttestationStatementSupport
    {
        return TPMAttestationStatementSupport::create($clock);
    }

    public function attestation(AttestationStatementSupport $support): AttestationStatementSupport
    {
        return new Rs1RefusingAttestationStatementSupport($support);
    }

    /**
     * @return non-empty-list<Algorithm>
     */
    private static function algorithms(): array
    {
        return [ES384::create(), Ed25519::create(), ES256::create(), RS256::create()];
    }
}
