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
 * RFC 8292 §2
 */
interface ApplicationServerKeyProviderInterface
{
    /**
     * @throws KeyNotFoundException
     */
    public function activeApplicationServerKey(Tenant $tenant, ApplicationServerAlgorithmInterface $algorithm): TenantKey;

    /**
     * @throws KeyNotFoundException when the tenant has no such key, or it has retired
     * @throws KmsUnavailableException
     */
    public function applicationServerKey(Tenant $tenant, string $kid): UnsealedKey;
}
