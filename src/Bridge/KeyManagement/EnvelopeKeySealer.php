<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\KeyManagement;

use Heidi\Core\Exception\RuntimeException;
use Heidi\Core\Port\KeySealerInterface;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use SensitiveParameter;
use Symfony\Component\KeyManagement\Envelope;
use Symfony\Component\KeyManagement\EnvelopeDecrypterInterface;
use Symfony\Component\KeyManagement\EnvelopeEncrypterInterface;
use Throwable;

/**
 * Seals private JWKs as symfony/key-management envelopes under one KMS key. The KMS wraps a data key per
 * envelope; the private JWK itself never reaches the KMS. The additional authenticated data binds an
 * envelope to this use: a sealed signing key cannot be passed off as something else, nor the reverse.
 */
final readonly class EnvelopeKeySealer implements KeySealerInterface
{
    private const string AAD = 'heidi:signing-key:v1';

    public function __construct(
        private EnvelopeEncrypterInterface&EnvelopeDecrypterInterface $envelopes,
        private string $kmsKeyId,
    ) {
    }

    public function seal(#[SensitiveParameter] array $privateJwk): string
    {
        $json = json_encode($privateJwk, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return (string) $this->envelopes->encrypt($this->kmsKeyId, $json, self::AAD);
    }

    public function unseal(#[SensitiveParameter] string $sealed): array
    {
        try {
            $json = $this->envelopes->decrypt(Envelope::fromBytes($sealed), self::AAD);
        } catch (Throwable $exception) {
            throw new RuntimeException('The sealed signing key could not be opened.', 0, $exception);
        }
        $jwk = json_decode($json, true, 8, JSON_THROW_ON_ERROR);
        if (! is_array($jwk)) {
            throw new RuntimeException('The sealed signing key does not contain a JWK.');
        }
        $members = [];
        foreach ($jwk as $member => $value) {
            if (! is_string($member) || ! is_string($value)) {
                throw new RuntimeException('The sealed signing key does not contain a JWK.');
            }
            $members[$member] = $value;
        }

        return $members;
    }
}
