<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Key;

use Beffroi\Core\Domain\Key\ApplicationServerAlgorithmInterface;
use Beffroi\Core\Domain\Key\KeptEncryptionAlgorithmInterface;
use Beffroi\Core\Domain\Key\KeptSigningAlgorithmInterface;
use Beffroi\Core\Domain\Key\KeyUse;
use Beffroi\Core\Domain\Key\MessageSealingAlgorithmInterface;
use Beffroi\Core\Domain\Key\PublicKeySet;
use Beffroi\Core\Domain\Key\SignatureAlgorithmInterface;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Key\UnsealedKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Port\ApplicationServerKeyProviderInterface;
use Beffroi\Core\Port\DecryptionKeyProviderInterface;
use Beffroi\Core\Port\KeptDecryptionKeyProviderInterface;
use Beffroi\Core\Port\KeptSigningKeyProviderInterface;
use Beffroi\Core\Port\MessageKeyProviderInterface;
use Beffroi\Core\Port\PublicKeySetProviderInterface;
use Beffroi\Core\Port\SigningKeyProviderInterface;
use Beffroi\Core\Port\TenantKeyRepositoryInterface;
use function sprintf;

/**
 * Which key answers is read from the repository on each call, so that a rotation or a retirement is seen at
 * once. The use of a key is asserted here, never deduced from the caller (RFC 7517 §4.2).
 */
final readonly class SealedKeyProvider implements
    ApplicationServerKeyProviderInterface,
    DecryptionKeyProviderInterface,
    KeptDecryptionKeyProviderInterface,
    KeptSigningKeyProviderInterface,
    MessageKeyProviderInterface,
    PublicKeySetProviderInterface,
    SigningKeyProviderInterface
{
    public function __construct(
        private TenantKeyRepositoryInterface $keys,
        private OpenedKeys $opened,
    ) {
    }

    public function activeKey(Tenant $tenant, SignatureAlgorithmInterface $algorithm): UnsealedKey
    {
        $key = $this->keys->findActive($tenant, $algorithm);
        if ($key === null) {
            throw new KeyNotFoundException(sprintf('Tenant "%s" has no active %s signing key.', $tenant->slug, $algorithm->name()));
        }
        self::assertUse($key, KeyUse::Signature);
        $this->opened->forgetOthers($tenant, $key);

        return $this->opened->open($tenant, $key);
    }

    public function activeKeptSigningKey(Tenant $tenant, KeptSigningAlgorithmInterface $algorithm): UnsealedKey
    {
        $key = $this->keys->findActive($tenant, $algorithm);
        if ($key === null) {
            throw new KeyNotFoundException(sprintf('Tenant "%s" has no active %s key to sign with.', $tenant->slug, $algorithm->name()));
        }
        self::assertUse($key, KeyUse::Signature);
        $this->opened->forgetOthers($tenant, $key);

        return $this->opened->open($tenant, $key);
    }

    public function decryptionKey(Tenant $tenant, string $kid): UnsealedKey
    {
        $key = $this->keys->find($tenant, $kid);
        if ($key === null) {
            throw new KeyNotFoundException(sprintf('Tenant "%s" publishes no key "%s".', $tenant->slug, $kid));
        }
        if (! $key->isInService()) {
            $this->opened->forget($tenant, $kid);

            throw new KeyNotFoundException(sprintf(
                'The key "%s" of tenant "%s" has retired: what was encrypted to it is not opened any more.',
                $kid,
                $tenant->slug,
            ));
        }
        if (! $key->isPublished()) {
            throw new KeyNotFoundException(sprintf(
                'The key "%s" of tenant "%s" is one it keeps; it is not a key a client encrypts to.',
                $kid,
                $tenant->slug,
            ));
        }
        self::assertUse($key, KeyUse::Encryption);

        return $this->opened->open($tenant, $key);
    }

    public function keptDecryptionKeys(Tenant $tenant, KeptEncryptionAlgorithmInterface $algorithm, array $kids): array
    {
        $this->opened->keepOnly($tenant, $algorithm, $kids);
        $opened = [];
        foreach ($kids as $kid) {
            $opened[] = $this->keptDecryptionKey($tenant, $algorithm, $kid);
        }

        return $opened;
    }

    /**
     * @throws KeyNotFoundException
     */
    private function keptDecryptionKey(Tenant $tenant, KeptEncryptionAlgorithmInterface $algorithm, string $kid): UnsealedKey
    {
        $key = $this->keys->find($tenant, $kid);
        if ($key === null) {
            throw new KeyNotFoundException(sprintf('Tenant "%s" holds no key "%s".', $tenant->slug, $kid));
        }
        if (! $key->isInService()) {
            $this->opened->forget($tenant, $kid);

            throw new KeyNotFoundException(sprintf(
                'The key "%s" of tenant "%s" has retired: what was encrypted to it is not opened any more.',
                $kid,
                $tenant->slug,
            ));
        }
        // The algorithm names whom the key belongs to: another holder's key is never answered, whatever its kid.
        if ($key->algorithm->name() !== $algorithm->name()) {
            throw new KeyNotFoundException(sprintf(
                'The key "%s" of tenant "%s" is a key of %s, not of %s.',
                $kid,
                $tenant->slug,
                $key->algorithm->name(),
                $algorithm->name(),
            ));
        }
        self::assertUse($key, KeyUse::Encryption);

        return $this->opened->open($tenant, $key);
    }

    public function sealingKey(Tenant $tenant, MessageSealingAlgorithmInterface $algorithm): TenantKey
    {
        $sealing = null;
        // The newest in service, the keys coming in creation order, and not the active one: a key the tenant never
        // publishes has nobody to wait through a staging window for.
        foreach ($this->keys->findInService($tenant) as $key) {
            if ($key->algorithm->name() === $algorithm->name()) {
                $sealing = $key;
            }
        }

        return $sealing ?? throw new KeyNotFoundException(sprintf(
            'Tenant "%s" holds no %s key: it is the first key a tenant is given, and nothing it writes to a transport leaves the process before it exists.',
            $tenant->slug,
            $algorithm->name(),
        ));
    }

    public function messageKey(Tenant $tenant, string $kid): UnsealedKey
    {
        $key = $this->keys->find($tenant, $kid);
        if ($key === null || ! $key->isInService()) {
            $this->opened->forget($tenant, $kid);

            throw new KeyNotFoundException(sprintf(
                'Tenant "%s" seals nothing to "%s" any more: a body sealed to it is not opened.',
                $tenant->slug,
                $kid,
            ));
        }
        if (! $key->algorithm instanceof MessageSealingAlgorithmInterface) {
            throw new KeyNotFoundException(sprintf(
                'The key "%s" of tenant "%s" is not one it seals what leaves the process to.',
                $kid,
                $tenant->slug,
            ));
        }

        return $this->opened->open($tenant, $key);
    }

    public function publicKeys(Tenant $tenant): PublicKeySet
    {
        return PublicKeySet::published($this->keys->findInService($tenant));
    }

    public function activeApplicationServerKey(Tenant $tenant, ApplicationServerAlgorithmInterface $algorithm): TenantKey
    {
        return (
            $this->keys->findActive($tenant, $algorithm) ?? throw new KeyNotFoundException(sprintf(
                'Tenant "%s" has no active %s key to hand out.',
                $tenant->slug,
                $algorithm->name(),
            ))
        );
    }

    public function applicationServerKey(Tenant $tenant, string $kid): UnsealedKey
    {
        $key = $this->keys->find($tenant, $kid);
        if ($key === null || ! $key->isInService()) {
            $this->opened->forget($tenant, $kid);

            throw new KeyNotFoundException(sprintf(
                'Tenant "%s" hands out no key "%s" any more: what was bound to it is worth nothing.',
                $tenant->slug,
                $kid,
            ));
        }
        if (! $key->algorithm instanceof ApplicationServerAlgorithmInterface) {
            throw new KeyNotFoundException(sprintf(
                'The key "%s" of tenant "%s" is published in its JWK Set; it is not a key it hands out.',
                $kid,
                $tenant->slug,
            ));
        }

        return $this->opened->open($tenant, $key);
    }

    /**
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
