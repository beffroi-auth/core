<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\KeyManagement;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\SecretSealerInterface;
use SensitiveParameter;
use Symfony\Component\KeyManagement\EnvelopeDecrypterInterface;
use Symfony\Component\KeyManagement\EnvelopeEncrypterInterface;

/**
 * Seals the shared secrets of the users as symfony/key-management envelopes under the KMS key of the
 * tenant, the same envelopes and the same KMS as the signing keys (EnvelopeKeySealer); the secret never
 * reaches the KMS. A secret goes in the envelope as it is, so this is a TenantEnvelope of its own use and
 * nothing more: an envelope of a signing key does not open as a secret, nor a secret of one tenant for
 * another.
 */
final readonly class EnvelopeSecretSealer implements SecretSealerInterface
{
    private const string AAD = 'beffroi:shared-secret:v1:';

    private TenantEnvelope $envelope;

    /**
     * @param string $kmsKeyId "{tenant}" stands for the slug of the tenant
     */
    public function __construct(
        EnvelopeEncrypterInterface&EnvelopeDecrypterInterface $envelopes,
        string $kmsKeyId,
    ) {
        $this->envelope = new TenantEnvelope($envelopes, $kmsKeyId, self::AAD, 'secret');
    }

    public function seal(Tenant $tenant, #[SensitiveParameter] string $secret): string
    {
        return $this->envelope->seal($tenant, $secret);
    }

    public function unseal(Tenant $tenant, #[SensitiveParameter] string $sealed): string
    {
        return $this->envelope->open($tenant, $sealed);
    }
}
