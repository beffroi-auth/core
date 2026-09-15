<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Event;

use DateTimeImmutable;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;

/**
 * Every event of the core says when it happened, in which tenant and from which request. The core announces;
 * the integration records, enriches and reacts, asynchronously (architecture §3.5).
 */
interface DomainEventInterface
{
    public DateTimeImmutable $occurredAt { get; }

    public Tenant $tenant { get; }

    public RequestContext $context { get; }
}
