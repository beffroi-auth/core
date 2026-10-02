<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * A new key is published and signs nothing yet.
 */
final readonly class TenantKeyStaged implements DomainEventInterface
{
    public Tenant $tenant;

    public function __construct(
        public TenantKey $staged,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
        $this->tenant = $staged->tenant;
    }
}
