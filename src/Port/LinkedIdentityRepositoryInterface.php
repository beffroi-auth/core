<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\LinkedIdentity;
use Beffroi\Core\Domain\Tenant\Tenant;

interface LinkedIdentityRepositoryInterface
{
    /**
     * @param string $subject compared byte for byte
     */
    public function find(Tenant $tenant, string $provider, string $subject): ?LinkedIdentity;

    public function save(LinkedIdentity $identity): void;
}
