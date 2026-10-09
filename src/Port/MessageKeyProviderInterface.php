<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\MessageSealingAlgorithmInterface;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Key\UnsealedKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;

/**
 * What seals a message body and what opens it afterwards (decision 0020): the active key of the tenant,
 * whose public half is on the line in the clear, and the whole of the key a sealed body names.
 *
 * Two questions and not one, because the two sides pay different prices. Sealing happens on the path of a
 * request, which raises several events for one login, and it asks the KMS nothing: a public half opens
 * nothing, so there is nothing to open. Opening happens in a worker, which lives long enough to pay the
 * KMS once for a key and keep it (decision 0018).
 */
interface MessageKeyProviderInterface
{
    /**
     * The key a body is sealed to: its public half, in the clear on the line, and its "kid", which the
     * protected header of the JWE names. Nothing of the KMS is asked here.
     *
     * @throws KeyNotFoundException when the tenant has no active key for the algorithm, which is a tenant
     *                              whose keys have not been generated: a body is then not written at all
     *                              rather than written in the clear
     */
    public function activeMessageKey(Tenant $tenant, MessageSealingAlgorithmInterface $algorithm): TenantKey;

    /**
     * The key a sealed body names, opened by the KMS: the private half, which a consumer keeps for as long
     * as the process lives.
     *
     * @throws KeyNotFoundException when the tenant holds no such key any more, which is what a retirement
     *                              means: a body sealed to it waited longer than its window
     * @throws KmsUnavailableException when the KMS does not answer to open it
     */
    public function messageKey(Tenant $tenant, string $kid): UnsealedKey;
}
