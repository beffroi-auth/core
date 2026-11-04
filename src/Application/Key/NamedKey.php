<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Key;

use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Port\TenantKeyRepositoryInterface;
use function sprintf;

final readonly class NamedKey
{
    public function __construct(
        private TenantKeyRepositoryInterface $keys,
    ) {
    }

    /**
     * @throws KeyNotFoundException
     */
    public function of(Tenant $tenant, string $kid): TenantKey
    {
        return $this->keys->find($tenant, $kid) ?? throw new KeyNotFoundException(sprintf('Tenant "%s" has no key "%s".', $tenant->slug, $kid));
    }
}
