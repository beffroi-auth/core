<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\Key;

use Heidi\Core\Domain\Key\PublicKeySet;
use Heidi\Core\Domain\Key\SignatureAlgorithm;
use Heidi\Core\Domain\Key\UnsealedSigningKey;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\KeyNotFoundException;
use Heidi\Core\Port\KeySealerInterface;
use Heidi\Core\Port\SigningKeyProviderInterface;
use Heidi\Core\Port\SigningKeyRepositoryInterface;
use function sprintf;

/**
 * Reads the keys of a tenant from the repository and opens the active one with the KMS. An opened key stays
 * in the memory of this instance for the rest of the process, so that the KMS is reached once per key, not
 * once per token.
 */
final class SealedKeyProvider implements SigningKeyProviderInterface
{
    /**
     * @var array<string, UnsealedSigningKey> by kid
     */
    private array $opened = [];

    public function __construct(
        private readonly SigningKeyRepositoryInterface $keys,
        private readonly KeySealerInterface $sealer,
    ) {
    }

    public function activeKey(Tenant $tenant, SignatureAlgorithm $algorithm): UnsealedSigningKey
    {
        $key = $this->keys->findActive($tenant, $algorithm);
        if ($key === null) {
            throw new KeyNotFoundException(sprintf('Tenant "%s" has no active %s signing key.', $tenant->slug, $algorithm->value));
        }

        return $this->opened[$key->kid] ??= new UnsealedSigningKey($key, $this->sealer->unseal($key->sealedPrivateJwk));
    }

    public function publicKeys(Tenant $tenant): PublicKeySet
    {
        return new PublicKeySet($this->keys->findPublished($tenant));
    }
}
