<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\SignatureAlgorithmInterface;
use Beffroi\Core\Domain\Key\UnsealedKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;

interface SigningKeyProviderInterface
{
    /**
     * @throws KeyNotFoundException
     * @throws KmsUnavailableException
     */
    public function activeKey(Tenant $tenant, SignatureAlgorithmInterface $algorithm): UnsealedKey;
}
