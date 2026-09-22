<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\KeyManagement;

use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Port\SecretSealerInterface;
use SensitiveParameter;
use function sprintf;
use Symfony\Component\KeyManagement\Envelope;
use Symfony\Component\KeyManagement\EnvelopeDecrypterInterface;
use Symfony\Component\KeyManagement\EnvelopeEncrypterInterface;
use Throwable;

/**
 * Seals the shared secrets of the users as symfony/key-management envelopes under the KMS key of the
 * tenant, the same envelopes and the same KMS as the signing keys (EnvelopeKeySealer); the secret never
 * reaches the KMS. The additional authenticated data binds an envelope to this use and to its tenant: an
 * envelope of a signing key does not open as a secret, nor a secret of one tenant for another. A KMS that
 * does not answer is told from an envelope it refuses (KeyManagementFailure).
 */
final readonly class EnvelopeSecretSealer implements SecretSealerInterface
{
    private const string AAD = 'heidi:shared-secret:v1:';

    private KmsKeyId $kmsKeyId;

    /**
     * @param string $kmsKeyId "{tenant}" stands for the slug of the tenant
     */
    public function __construct(
        private EnvelopeEncrypterInterface&EnvelopeDecrypterInterface $envelopes,
        string $kmsKeyId,
    ) {
        $this->kmsKeyId = new KmsKeyId($kmsKeyId);
    }

    public function seal(Tenant $tenant, #[SensitiveParameter] string $secret): string
    {
        try {
            return (string) $this->envelopes->encrypt($this->kmsKeyId->of($tenant), $secret, self::aad($tenant));
        } catch (Throwable $exception) {
            throw KeyManagementFailure::of($exception, sprintf(
                'A secret of tenant "%s" could not be sealed under the KMS key "%s".',
                $tenant->slug,
                $this->kmsKeyId->of($tenant),
            ));
        }
    }

    public function unseal(Tenant $tenant, #[SensitiveParameter] string $sealed): string
    {
        try {
            return $this->envelopes->decrypt(Envelope::fromBytes($sealed), self::aad($tenant));
        } catch (Throwable $exception) {
            throw KeyManagementFailure::of($exception, 'The sealed secret could not be opened.');
        }
    }

    private static function aad(Tenant $tenant): string
    {
        return self::AAD . $tenant->slug;
    }
}
