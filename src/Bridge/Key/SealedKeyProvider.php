<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\Key;

use Heidi\Core\Domain\Key\KeyUse;
use Heidi\Core\Domain\Key\PublicKeySet;
use Heidi\Core\Domain\Key\SignatureAlgorithmInterface;
use Heidi\Core\Domain\Key\TenantKey;
use Heidi\Core\Domain\Key\UnsealedKey;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\KeyNotFoundException;
use Heidi\Core\Port\DecryptionKeyProviderInterface;
use Heidi\Core\Port\KeySealerInterface;
use Heidi\Core\Port\PublicKeySetProviderInterface;
use Heidi\Core\Port\SigningKeyProviderInterface;
use Heidi\Core\Port\TenantKeyRepositoryInterface;
use function sprintf;

/**
 * Opens a key of a tenant with the KMS once and keeps it in memory; which key is the one asked for is read
 * from the repository on each call, so that a rotation, and a retirement, are seen at once.
 *
 * The three capabilities of a key store are three ports here, and a caller holds the one it uses: what
 * signs asks for the active key of an algorithm, what opens a JWE asks for the published encryption key a
 * header names, what publishes asks for the set. A key that signs is never handed out to open a JWE, nor
 * one that opens a JWE to sign: the use is asserted here and not deduced from the caller (RFC 7517 §4.2).
 */
final class SealedKeyProvider implements DecryptionKeyProviderInterface, PublicKeySetProviderInterface, SigningKeyProviderInterface
{
    /**
     * @var array<string, UnsealedKey> by tenant and kid: a "kid" is the thumbprint of the public key
     * (RFC 7638), so two tenants that hold the same material name it the same, and an envelope opened
     * for one is never the answer for the other. What the KMS binds to a tenant, this binds too.
     */
    private array $opened = [];

    public function __construct(
        private readonly TenantKeyRepositoryInterface $keys,
        private readonly KeySealerInterface $sealer,
    ) {
    }

    public function activeKey(Tenant $tenant, SignatureAlgorithmInterface $algorithm): UnsealedKey
    {
        $key = $this->keys->findActive($tenant, $algorithm);
        if ($key === null) {
            throw new KeyNotFoundException(sprintf('Tenant "%s" has no active %s signing key.', $tenant->slug, $algorithm->name()));
        }
        self::assertUse($key, KeyUse::Signature);
        foreach ($this->opened as $index => $opened) {
            if ($opened->key->kid !== $key->kid && $opened->key->tenant->equals($tenant) && $opened->key->algorithm->name() === $algorithm->name()) {
                unset($this->opened[$index]);
            }
        }

        return $this->open($tenant, $key);
    }

    public function decryptionKey(Tenant $tenant, string $kid): UnsealedKey
    {
        $key = $this->keys->find($tenant, $kid);
        if ($key === null) {
            throw new KeyNotFoundException(sprintf('Tenant "%s" publishes no key "%s".', $tenant->slug, $kid));
        }
        if (! $key->isPublished()) {
            unset($this->opened[self::index($tenant, $kid)]);

            throw new KeyNotFoundException(sprintf(
                'The key "%s" of tenant "%s" has retired: what was encrypted to it is not opened any more.',
                $kid,
                $tenant->slug,
            ));
        }
        self::assertUse($key, KeyUse::Encryption);

        return $this->open($tenant, $key);
    }

    public function publicKeys(Tenant $tenant): PublicKeySet
    {
        return new PublicKeySet($this->keys->findPublished($tenant));
    }

    /**
     * A key is opened once and stays open while it is the one answered with; the KMS is asked once.
     */
    private function open(Tenant $tenant, TenantKey $key): UnsealedKey
    {
        return $this->opened[self::index($tenant, $key->kid)] ??= new UnsealedKey($key, $this->sealer->unseal($tenant, $key->sealedPrivateJwk));
    }

    private static function index(Tenant $tenant, string $kid): string
    {
        return $tenant->id->toRfc4122() . '/' . $kid;
    }

    /**
     * The store could hold a key of the other use under that identifier; it is refused rather than used
     * for what it was not published for.
     *
     * @throws KeyNotFoundException
     */
    private static function assertUse(TenantKey $key, KeyUse $expected): void
    {
        $use = $key->algorithm->keyUse();
        if ($use === $expected) {
            return;
        }

        throw new KeyNotFoundException(sprintf(
            'The key "%s" of tenant "%s" is published with "use": "%s"; it is not a key that %s.',
            $key->kid,
            $key->tenant->slug,
            $use->value,
            $expected === KeyUse::Signature ? 'signs' : 'opens a JWE',
        ));
    }
}
