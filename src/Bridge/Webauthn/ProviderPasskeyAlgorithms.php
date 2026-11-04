<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use function array_map;
use Cose\Algorithm\Algorithm;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\EdDSA\Ed25519;
use Cose\Algorithm\Signature\RSA\RS256;
use Psr\Clock\ClockInterface;
use Webauthn\AttestationStatement\AttestationStatementSupport;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\PublicKeyCredentialParameters;

/**
 * RFC 9053 §2.2, RFC 8812 §2
 *
 * No ES384: Windows Hello creates a passkey of it when the options name it first, and Chrome on Windows does
 * not survive that passkey.
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
        return [Ed25519::create(), ES256::create(), RS256::create()];
    }
}
