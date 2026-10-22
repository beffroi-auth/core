<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Identity\UpstreamProvider;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\UpstreamProvidersInterface;

/**
 * For tests and for the examples: the upstream providers declared for each tenant, none by default.
 */
final class InMemoryUpstreamProviders implements UpstreamProvidersInterface
{
    /**
     * @var array<string, list<UpstreamProvider>> by the identifier of the tenant
     */
    private array $providers = [];

    public function declare(Tenant $tenant, UpstreamProvider $provider): void
    {
        $this->providers[$tenant->id->toRfc4122()][] = $provider;
    }

    public function of(Tenant $tenant): array
    {
        return $this->providers[$tenant->id->toRfc4122()] ?? [];
    }
}
