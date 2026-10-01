<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Event;

use DateTimeImmutable;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;

/**
 * The data keys of a tenant are gone, and with them every personal datum that tenant had sealed: the
 * columns hold their ciphertexts and nothing opens them again, by anybody.
 *
 * It is the one event of this provider that records an erasure rather than a change, which is why it says
 * how many keys and over how many scopes: after it, that is what cannot be counted any more.
 */
final readonly class TenantDataKeysDestroyed implements DomainEventInterface
{
    /**
     * @param int $keys how many wrapped keys were erased
     * @param int $scopes over how many scopes, which is how many families of columns the tenant had sealed
     */
    public function __construct(
        public Tenant $tenant,
        public int $keys,
        public int $scopes,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
    }
}
