<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Event;

use DateTimeImmutable;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;

/**
 * When, in which tenant, from which request. The core announces; the integration reacts asynchronously (architecture §3.5).
 *
 * An event is built where it is raised: its constructor takes exactly what it carries, and nothing on it
 * reads a domain object to fill it. What it carries is what survives the queue it is written to, so it
 * holds a domain object only when that object leaves the process whole, and the facts by identifier
 * otherwise: an object that carries infrastructure, as a client carries the reader of its "jwks_uri",
 * could not be written to a broker at all.
 */
interface DomainEventInterface
{
    public DateTimeImmutable $occurredAt { get; }

    public Tenant $tenant { get; }

    public RequestContext $context { get; }
}
