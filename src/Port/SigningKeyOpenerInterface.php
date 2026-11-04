<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\OpenedSigningKeyInterface;
use Beffroi\Core\Domain\Key\SignatureAlgorithmInterface;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;

/**
 * Signs in two steps: a caller that spends what it cannot get back opens the key first, so that an outage of
 * the KMS costs it nothing.
 */
interface SigningKeyOpenerInterface
{
    /**
     * @throws KeyNotFoundException
     * @throws KmsUnavailableException
     */
    public function open(Tenant $tenant, SignatureAlgorithmInterface $algorithm): OpenedSigningKeyInterface;
}
