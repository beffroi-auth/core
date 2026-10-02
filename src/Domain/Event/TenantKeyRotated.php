<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

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
