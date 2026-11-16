<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Webauthn;

use Beffroi\Core\Domain\Tenant\AttestationRoot;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\AttestationRootReaderInterface;
use function sprintf;
use SpomkyLabs\Pki\CryptoEncoding\PEM;
use SpomkyLabs\Pki\X509\Certificate\Certificate;
use Throwable;

/**
 * RFC 5280 §4.2.1.9
 */
final readonly class PkiAttestationRootReader implements AttestationRootReaderInterface
{
    public function read(string $pem): AttestationRoot
    {
        try {
            $certificate = Certificate::fromPEM(PEM::fromString($pem));
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                sprintf('A root of passkey attestations is a certificate in PEM: %s', $exception->getMessage()),
                previous: $exception,
            );
        }
        $extensions = $certificate->tbsCertificate()->extensions();
        if (! $extensions->hasBasicConstraints() || ! $extensions->basicConstraints()->isCA()) {
            throw new InvalidArgumentException(sprintf(
                'The root of passkey attestations "%s" is the certificate of no certificate authority (RFC 5280 §4.2.1.9).',
                $certificate->tbsCertificate()->subject()->toString(),
            ));
        }

        return new AttestationRoot($certificate->toPEM()->string());
    }
}
