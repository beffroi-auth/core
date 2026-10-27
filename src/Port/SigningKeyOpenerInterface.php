<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\OpenedSigningKeyInterface;
use Beffroi\Core\Domain\Key\SignatureAlgorithmInterface;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;

/**
 * What signs in two steps: the active key of the tenant for one algorithm opened first, which is the step that can
 * fail on a KMS that does not answer, and what is signed under it afterwards, which asks nothing of the KMS. A
 * caller that spends what it cannot get back opens the key before it spends and signs after, so that an outage of
 * the KMS costs it nothing (architecture §3.3); a caller that spends nothing signs in one step (JwsSignerInterface).
 */
interface SigningKeyOpenerInterface
{
    /**
     * @throws KeyNotFoundException when the tenant has no active key for the algorithm
     * @throws KmsUnavailableException when the KMS does not answer to open the active key, which is not open yet
     */
    public function open(Tenant $tenant, SignatureAlgorithmInterface $algorithm): OpenedSigningKeyInterface;
}
