<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Event;

use DateTimeImmutable;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;

/**
 * When, in which tenant, from which request. The core announces; the integration reacts asynchronously (architecture §3.5).
 */
interface DomainEventInterface
{
    public DateTimeImmutable $occurredAt { get; }

    public Tenant $tenant { get; }

    public RequestContext $context { get; }
}
