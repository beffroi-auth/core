<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Key;

use Beffroi\Core\Domain\Key\ApplicationServerAlgorithmInterface;
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
use Beffroi\Core\Port\KeptSigningKeyProviderInterface;
use Beffroi\Core\Port\MessageKeyProviderInterface;
use Beffroi\Core\Port\PublicKeySetProviderInterface;
use Beffroi\Core\Port\SigningKeyProviderInterface;
use Beffroi\Core\Port\TenantKeyRepositoryInterface;
use function sprintf;

/**
 * Says which key of the store answers which question: which key is the one asked for is read from the
 * repository on each call, so that a rotation, and a retirement, are seen at once. The material opened
 * with the KMS is held by OpenedKeys, which this tells what is no longer an answer.
 *
 * The capabilities of a key store are a port each here, and a caller holds the one it uses: what signs
 * asks for the active key of an algorithm, what opens a JWE asks for the published encryption key a
 * header names, what publishes asks for the set, what pushes to a browser asks for the key that browser
 * was handed, what writes to a transport asks for the key the tenant seals to, what signs a SAML message
 * asks for the active key of a family the tenant keeps. A key that signs is never
 * handed out to open a JWE, nor one that opens a JWE to sign: the use is asserted here and not deduced
 * from the caller (RFC 7517 §4.2), and a family of keys is never answered to the caller of another.
 *
 * The JWK Set is what the tenant publishes to whoever reads its issuer, so the keys it keeps are not in
 * it and are never answered to a reader of it: the key the browser is given is given by the page it
 * subscribes on, and the key a message body is sealed to would make that body forgeable by anyone who
 * read the set (decision 0020). Which keys those are is read from the algorithm
 * (PublishedKeyAlgorithmInterface) and not enumerated here, so a family added to either side needs no
 * line in this class.
 */
final readonly class SealedKeyProvider implements
    ApplicationServerKeyProviderInterface,
    DecryptionKeyProviderInterface,
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

    public function sealingKey(Tenant $tenant, MessageSealingAlgorithmInterface $algorithm): TenantKey
    {
        $sealing = null;
        // the newest in service and not the active one: the keys come in creation order, and a key the
        // tenant never publishes has nobody to wait through a staging window for (issue #548)
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
