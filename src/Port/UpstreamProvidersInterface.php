<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\UpstreamProvider;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * The upstream providers a tenant declares, whatever their protocol (decision 0029): what the domains of the
 * tenant that are federated, and the provider each is federated to, are read from (FederatedDomains). A
 * deployment that declares none answers none.
 */
interface UpstreamProvidersInterface
{
    /**
     * @return list<UpstreamProvider> in the order of the declaration
     */
    public function of(Tenant $tenant): array;
}
