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
use function sprintf;
use Symfony\Component\KeyManagement\Envelope;
use Symfony\Component\KeyManagement\EnvelopeDecrypterInterface;
use Symfony\Component\KeyManagement\EnvelopeEncrypterInterface;
use Throwable;

/**
 * Seals private JWKs as symfony/key-management envelopes; the private JWK never reaches the KMS. The
 * additional authenticated data binds an envelope to this use and to its tenant. A KMS that does not
 * answer is told from an envelope it refuses (KeyManagementFailure).
 */
final readonly class EnvelopeKeySealer implements KeySealerInterface
{
    private const string AAD = 'heidi:signing-key:v2:';

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

    public function seal(Tenant $tenant, #[SensitiveParameter] array $privateJwk): string
    {
        $json = json_encode($privateJwk, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        try {
            return (string) $this->envelopes->encrypt($this->kmsKeyId->of($tenant), $json, self::aad($tenant));
        } catch (Throwable $exception) {
            throw KeyManagementFailure::of($exception, sprintf('The signing key of tenant "%s" could not be sealed under the KMS key "%s".', $tenant->slug, $this->kmsKeyId->of($tenant)));
        }
    }

    public function unseal(Tenant $tenant, #[SensitiveParameter] string $sealed): array
    {
        try {
            $json = $this->envelopes->decrypt(Envelope::fromBytes($sealed), self::aad($tenant));
        } catch (Throwable $exception) {
            throw KeyManagementFailure::of($exception, 'The sealed signing key could not be opened.');
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

    private static function aad(Tenant $tenant): string
    {
        return self::AAD . $tenant->slug;
    }
}
