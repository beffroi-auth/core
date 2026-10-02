<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\KeyManagement;

use Beffroi\Core\Domain\Tenant\Tenant;
use SensitiveParameter;
use function sprintf;
use Symfony\Component\KeyManagement\Envelope;
use Symfony\Component\KeyManagement\EnvelopeDecrypterInterface;
use Symfony\Component\KeyManagement\EnvelopeEncrypterInterface;
use Throwable;

/**
 * An envelope of one use under the KMS key of a tenant (architecture §3.3), which is what every sealer of
 * this bridge is: the plaintext never reaches the KMS, the additional authenticated data binds the
 * envelope to its use and to its tenant, and a KMS that does not answer is told from an envelope it
 * refuses (KeyManagementFailure). Sealed for one tenant, it does not open for another; sealed for one
 * use, it does not open for another.
 *
 * What a sealer adds to this is what it puts in the envelope: a private JWK as JSON (EnvelopeKeySealer),
 * a shared secret as it is (EnvelopeSecretSealer). The binding, the classification of a failure and the
 * resolution of the KMS key of the tenant are written here and once.
 */
final readonly class TenantEnvelope
{
    private KmsKeyId $kmsKeyId;

    /**
     * @param string $kmsKeyId "{tenant}" stands for the slug of the tenant
     * @param string $purpose the additional authenticated data of this use, the prefix the slug of the
     *                        tenant is appended to; the wording of the day it was written, which never
     *                        changes, because nothing rewraps an envelope sealed before
     * @param string $contents how a failure names what the envelope holds ("key", "secret")
     */
    public function __construct(
        private EnvelopeEncrypterInterface&EnvelopeDecrypterInterface $envelopes,
        string $kmsKeyId,
        private string $purpose,
        private string $contents,
    ) {
        $this->kmsKeyId = new KmsKeyId($kmsKeyId);
    }

    public function seal(Tenant $tenant, #[SensitiveParameter] string $plaintext): string
    {
        try {
            return (string) $this->envelopes->encrypt($this->kmsKeyId->of($tenant), $plaintext, $this->aad($tenant));
        } catch (Throwable $exception) {
            throw KeyManagementFailure::of($exception, sprintf(
                'The %s of tenant "%s" could not be sealed under the KMS key "%s".',
                $this->contents,
                $tenant->slug,
                $this->kmsKeyId->of($tenant),
            ));
        }
    }

    public function open(Tenant $tenant, #[SensitiveParameter] string $sealed): string
    {
        try {
            return $this->envelopes->decrypt(Envelope::fromBytes($sealed), $this->aad($tenant));
        } catch (Throwable $exception) {
            throw KeyManagementFailure::of($exception, sprintf('The sealed %s could not be opened.', $this->contents));
        }
    }

    private function aad(Tenant $tenant): string
    {
        return $this->purpose . $tenant->slug;
    }
}
