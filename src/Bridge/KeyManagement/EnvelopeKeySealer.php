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
use function strtr;
use Symfony\Component\KeyManagement\Envelope;
use Symfony\Component\KeyManagement\EnvelopeDecrypterInterface;
use Symfony\Component\KeyManagement\EnvelopeEncrypterInterface;
use Throwable;

/**
 * Seals private JWKs as symfony/key-management envelopes. The KMS wraps a data key per envelope; the
 * private JWK itself never reaches the KMS. The KMS key is named from the tenant: "{tenant}" in the key
 * identifier stands for its slug, so that each tenant has its own master key and a KMS policy can scope a
 * credential to one; a literal identifier is one key for all. The additional authenticated data binds an
 * envelope to this use and to its tenant: a sealed signing key cannot be passed off as something else, nor
 * as the key of another tenant, nor the reverse.
 */
final readonly class EnvelopeKeySealer implements KeySealerInterface
{
    public const string TENANT_PLACEHOLDER = '{tenant}';

    private const string AAD = 'heidi:signing-key:v2:';

    /**
     * @param string $kmsKeyId the identifier of the KMS key, "{tenant}" standing for the slug of the tenant
     */
    public function __construct(
        private EnvelopeEncrypterInterface&EnvelopeDecrypterInterface $envelopes,
        private string $kmsKeyId,
    ) {
    }

    public function seal(Tenant $tenant, #[SensitiveParameter] array $privateJwk): string
    {
        $json = json_encode($privateJwk, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        try {
            return (string) $this->envelopes->encrypt($this->kmsKeyId($tenant), $json, self::aad($tenant));
        } catch (Throwable $exception) {
            throw new RuntimeException(sprintf('The signing key of tenant "%s" could not be sealed under the KMS key "%s".', $tenant->slug, $this->kmsKeyId($tenant)), 0, $exception);
        }
    }

    public function unseal(Tenant $tenant, #[SensitiveParameter] string $sealed): array
    {
        try {
            $json = $this->envelopes->decrypt(Envelope::fromBytes($sealed), self::aad($tenant));
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

    private function kmsKeyId(Tenant $tenant): string
    {
        return strtr($this->kmsKeyId, [
            self::TENANT_PLACEHOLDER => $tenant->slug,
        ]);
    }

    private static function aad(Tenant $tenant): string
    {
        return self::AAD . $tenant->slug;
    }
}
