<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\UpstreamProvider;
use Beffroi\Core\Domain\Tenant\Tenant;

interface UpstreamProvidersInterface
{
    /**
     * @return list<UpstreamProvider>
     */
    public function of(Tenant $tenant): array;
}
