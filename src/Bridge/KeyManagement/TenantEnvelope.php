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

final readonly class TenantEnvelope
{
    private KmsKeyId $kmsKeyId;

    /**
     * @param string $kmsKeyId "{tenant}" stands for the slug of the tenant
     * @param string $purpose the prefix of the additional authenticated data, which never changes
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
