<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\KeyManagement;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\RuntimeException;
use Beffroi\Core\Port\KeySealerInterface;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use SensitiveParameter;
use Symfony\Component\KeyManagement\EnvelopeDecrypterInterface;
use Symfony\Component\KeyManagement\EnvelopeEncrypterInterface;

final readonly class EnvelopeKeySealer implements KeySealerInterface
{
    /**
     * Never changes, though it says "signing-key" of every key: nothing rewraps an envelope sealed under it.
     */
    private const string AAD = 'beffroi:signing-key:v2:';

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
