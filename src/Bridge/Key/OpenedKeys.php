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
 * The private halves a process holds open, asked of the KMS once and kept for as long as they are what
 * answers (decision 0018, architecture §3.3). A worker lives long enough for one opening to cover every
 * signature of a key and every message of a vintage, which is what keeps the KMS off the path of a
 * request; a process that is rebuilt at each request pays an opening at each request.
 *
 * What is held is ranged by tenant **and** by "kid", as the envelope that carries it is bound to both: a
 * "kid" is the thumbprint of the public key (RFC 7638), so two tenants that hold the same material name
 * it the same. What the additional authenticated data of an envelope bind to a tenant, this memory binds
 * too, instead of answering one with what was opened for the other.
 *
 * Forgetting is the other half of the same job and it is written here, once, rather than at each place
 * that answers: a private half that is no longer an answer has no reason to stay in the process. Two
 * gestures cover it, a key named by its "kid", which is what a retirement means for the key a subscription
 * or a sealed body names, and the others of a tenant and of an algorithm, which is what a rotation seen by
 * a worker leaves behind.
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
     * The key with its private half in the clear, opened by the KMS the first time and handed back as it
     * is afterwards.
     *
     * @throws KmsUnavailableException when the KMS does not answer to open the key, which is not open yet
     * @throws RuntimeException when the envelope was not sealed by this use for this tenant
     */
    public function open(Tenant $tenant, TenantKey $key): UnsealedKey
    {
        return $this->opened[self::index($tenant, $key->kid)] ??= new UnsealedKey($key, $this->sealer->unseal($tenant, $key->sealedPrivateJwk));
    }

    /**
     * The key of that tenant named by that "kid" is out of the process; asking for it again opens it
     * again. A "kid" that is not open is not an error: what answers reads the store on each call, so a
     * refusal forgets whether or not that key had ever been opened.
     */
    public function forget(Tenant $tenant, string $kid): void
    {
        unset($this->opened[self::index($tenant, $kid)]);
    }

    /**
     * The keys of that tenant and of the algorithm of that key, other than that key itself, are out of the
     * process: a tenant holds one key per algorithm that answers, and the one answered with is the one
     * worth keeping open.
     */
    public function forgetOthers(Tenant $tenant, TenantKey $answered): void
    {
        $this->keepOnly($tenant, $answered->algorithm, [$answered->kid]);
    }

    /**
     * The keys of that tenant and of that algorithm, other than those named, are out of the process: what answers for
     * an algorithm with several keys at once, the keys in service the key of a SAML identity provider is tried among,
     * names them all, and a key that has left the service since is no longer among them.
     *
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
