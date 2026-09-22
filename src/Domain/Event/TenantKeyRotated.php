<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Event;

use DateTimeImmutable;
use Heidi\Core\Domain\Key\TenantKey;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;

/**
 * A new key signs from now on; the previous active one, if any, entered its retirement window.
 */
final readonly class TenantKeyRotated implements DomainEventInterface
{
    public Tenant $tenant;

    public function __construct(
        public TenantKey $activated,
        public ?TenantKey $retiring,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
        $this->tenant = $activated->tenant;
    }
}
