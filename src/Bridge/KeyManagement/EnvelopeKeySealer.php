<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\KeyManagement;

use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\RuntimeException;
use Heidi\Core\Port\KeySealerInterface;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use SensitiveParameter;
use Symfony\Component\KeyManagement\EnvelopeDecrypterInterface;
use Symfony\Component\KeyManagement\EnvelopeEncrypterInterface;

/**
 * Seals private JWKs as symfony/key-management envelopes; the private JWK never reaches the KMS. A JWK
 * goes in the envelope as JSON, which is all this adds to the TenantEnvelope of its use.
 */
final readonly class EnvelopeKeySealer implements KeySealerInterface
{
    /**
     * What the envelope of a key of a tenant is, whatever the key is published for: it tells a key
     * envelope from a secret one (EnvelopeSecretSealer) and one tenant from another, never one use of a
     * key from the other, which the key itself carries. The wording is the one of the day it was written
     * and it does not change: every envelope sealed before the encryption keys of issue #164 opens under
     * it, and nothing rewraps an envelope (architecture §3.3).
     */
    private const string AAD = 'heidi:signing-key:v2:';

    private TenantEnvelope $envelope;

    /**
     * @param string $kmsKeyId "{tenant}" stands for the slug of the tenant
     */
    public function __construct(
        EnvelopeEncrypterInterface&EnvelopeDecrypterInterface $envelopes,
        string $kmsKeyId,
    ) {
        $this->envelope = new TenantEnvelope($envelopes, $kmsKeyId, self::AAD, 'key');
    }

    public function seal(Tenant $tenant, #[SensitiveParameter] array $privateJwk): string
    {
        return $this->envelope->seal($tenant, json_encode($privateJwk, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    public function unseal(Tenant $tenant, #[SensitiveParameter] string $sealed): array
    {
        $jwk = json_decode($this->envelope->open($tenant, $sealed), true, 8, JSON_THROW_ON_ERROR);
        if (! is_array($jwk)) {
            throw new RuntimeException('The sealed key does not contain a JWK.');
        }
        $members = [];
        foreach ($jwk as $member => $value) {
            if (! is_string($member) || ! is_string($value)) {
                throw new RuntimeException('The sealed key does not contain a JWK.');
            }
            $members[$member] = $value;
        }

        return $members;
    }
}
