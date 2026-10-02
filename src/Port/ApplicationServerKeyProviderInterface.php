<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\ApplicationServerAlgorithmInterface;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Key\UnsealedKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;

/**
 * What hands out a key of the tenant and what signs with it afterwards (RFC 8292 §2): the public half of
 * the active key, which is what a browser is given when it subscribes, and the whole of the key a
 * subscription was made under, which is what signs the request that pushes to it.
 *
 * Two questions and not one, because the two moments are not the same moment: a subscription made under a
 * key that has since started retiring is pushed to with that key until it retires, and a browser that
 * subscribes today is bound to the key that is active today (architecture §3.3).
 */
interface ApplicationServerKeyProviderInterface
{
    /**
     * The public half a browser is handed, and the "kid" the subscription it gets back is recorded under.
     *
     * @throws KeyNotFoundException when the tenant has no active key for the algorithm
     */
    public function activeApplicationServerKey(Tenant $tenant, ApplicationServerAlgorithmInterface $algorithm): TenantKey;

    /**
     * The key a subscription was made under, opened by the KMS.
     *
     * @throws KeyNotFoundException when the tenant publishes no such key any more, which is what a
     *                              retirement means: the subscriptions made under it are worth nothing
     * @throws KmsUnavailableException when the KMS does not answer to open it
     */
    public function applicationServerKey(Tenant $tenant, string $kid): UnsealedKey;
}
