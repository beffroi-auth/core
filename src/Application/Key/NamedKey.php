<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Key;

use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Port\TenantKeyRepositoryInterface;
use function sprintf;

/**
 * The key an operator named, or the refusal. Two steps of the life of a key are asked for by identifier
 * rather than handed a key, the take-over of one staged key and the retirement of one retiring key, so a
 * tenant that has no such key hears it in one place and in the same words.
 */
final readonly class NamedKey
{
    public function __construct(
        private TenantKeyRepositoryInterface $keys,
    ) {
    }

    /**
     * @throws KeyNotFoundException when the tenant holds no key of that identifier
     */
    public function of(Tenant $tenant, string $kid): TenantKey
    {
        return $this->keys->find($tenant, $kid) ?? throw new KeyNotFoundException(sprintf('Tenant "%s" has no key "%s".', $tenant->slug, $kid));
    }
}
