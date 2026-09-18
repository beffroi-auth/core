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
 * Opens the active key of a tenant with the KMS once and keeps it in memory while it is active; which key
 * is active is read from the repository on each call, so that a rotation is seen at once.
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
        foreach ($this->opened as $kid => $opened) {
            if ($kid !== $key->kid && $opened->key->tenant->equals($tenant) && $opened->key->algorithm === $algorithm) {
                unset($this->opened[$kid]);
            }
        }

        return $this->opened[$key->kid] ??= new UnsealedSigningKey($key, $this->sealer->unseal($tenant, $key->sealedPrivateJwk));
    }

    public function publicKeys(Tenant $tenant): PublicKeySet
    {
        return new PublicKeySet($this->keys->findPublished($tenant));
    }
}
