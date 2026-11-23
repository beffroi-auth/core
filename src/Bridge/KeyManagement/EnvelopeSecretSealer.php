<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\KeyManagement;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\SecretSealerInterface;
use SensitiveParameter;
use Symfony\Component\KeyManagement\EnvelopeDecrypterInterface;
use Symfony\Component\KeyManagement\EnvelopeEncrypterInterface;

/**
 * One purpose per sort of secret, so that an envelope of one sort never opens as another.
 */
final readonly class EnvelopeSecretSealer implements SecretSealerInterface
{
    /**
     * The secrets the provider keeps for itself, a TOTP secret in its row and the pairwise salt in its file: their
     * envelopes are sealed under it and never change purpose.
     */
    public const string KEPT = 'beffroi:shared-secret:v1:';

    private TenantEnvelope $envelope;

    /**
     * @param string $kmsKeyId "{tenant}" stands for the slug of the tenant
     * @param string $purpose the prefix of the additional authenticated data, the slug of the tenant following it
     */
    public function __construct(
        EnvelopeEncrypterInterface&EnvelopeDecrypterInterface $envelopes,
        string $kmsKeyId,
        string $purpose = self::KEPT,
    ) {
        $this->envelope = new TenantEnvelope($envelopes, $kmsKeyId, $purpose, 'secret');
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
