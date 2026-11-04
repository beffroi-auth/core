<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Key;

use function array_flip;
use Beffroi\Core\Domain\Key\KeyAlgorithmInterface;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Key\UnsealedKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KmsUnavailableException;
use Beffroi\Core\Exception\RuntimeException;
use Beffroi\Core\Port\KeySealerInterface;

/**
 * Held by tenant and by "kid": a "kid" is the RFC 7638 thumbprint, the same for two tenants holding the same
 * material.
 */
final class OpenedKeys
{
    /**
     * @var array<string, UnsealedKey> by tenant and kid
     */
    private array $opened = [];

    public function __construct(
        private readonly KeySealerInterface $sealer,
    ) {
    }

    /**
     * @throws KmsUnavailableException
     * @throws RuntimeException when the envelope was not sealed by this use for this tenant
     */
    public function open(Tenant $tenant, TenantKey $key): UnsealedKey
    {
        return $this->opened[self::index($tenant, $key->kid)] ??= new UnsealedKey($key, $this->sealer->unseal($tenant, $key->sealedPrivateJwk));
    }

    public function forget(Tenant $tenant, string $kid): void
    {
        unset($this->opened[self::index($tenant, $kid)]);
    }

    public function forgetOthers(Tenant $tenant, TenantKey $answered): void
    {
        $this->keepOnly($tenant, $answered->algorithm, [$answered->kid]);
    }

    /**
     * @param list<string> $kids
     */
    public function keepOnly(Tenant $tenant, KeyAlgorithmInterface $algorithm, array $kids): void
    {
        $kept = array_flip($kids);
        foreach ($this->opened as $index => $opened) {
            if (! isset($kept[$opened->key->kid]) && $opened->key->tenant->equals($tenant) && $opened->key->algorithm->name() === $algorithm->name()) {
                unset($this->opened[$index]);
            }
        }
    }

    private static function index(Tenant $tenant, string $kid): string
    {
        return $tenant->id->toRfc4122() . '/' . $kid;
    }
}
