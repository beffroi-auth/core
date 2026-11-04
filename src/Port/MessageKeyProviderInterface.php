<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\MessageSealingAlgorithmInterface;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Key\UnsealedKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;

interface MessageKeyProviderInterface
{
    /**
     * The newest key in service, staged included, and not the active one: otherwise a first key could never seal
     * the event that announces it.
     *
     * @throws KeyNotFoundException
     */
    public function sealingKey(Tenant $tenant, MessageSealingAlgorithmInterface $algorithm): TenantKey;

    /**
     * @throws KeyNotFoundException when the tenant holds no such key, or it has retired
     * @throws KmsUnavailableException
     */
    public function messageKey(Tenant $tenant, string $kid): UnsealedKey;
}
