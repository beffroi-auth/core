<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\LinkedIdentity;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * The identities a user of a tenant has at an upstream provider (decision 0002), recognised by the provider
 * and the subject that provider gave. A subject an upstream provider names is a user of this tenant only when
 * one of these rows says so; nothing invents one.
 */
interface LinkedIdentityRepositoryInterface
{
    /**
     * @param string $provider the upstream provider, as the registration that trusts it names it
     * @param string $subject the subject that provider gave, compared byte for byte
     *
     * @return LinkedIdentity|null null when no user of this tenant is that identity
     */
    public function find(Tenant $tenant, string $provider, string $subject): ?LinkedIdentity;

    public function save(LinkedIdentity $identity): void;
}
