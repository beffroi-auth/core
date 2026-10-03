<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

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
    /**
     * The name of the act, which is what the journal writes down (issue #454): a stable label an event
     * gives itself, as a credential names its own kind, and never a class name read by reflection. The
     * interface is what forces it: an event added to either package cannot reach the journal nameless, and
     * renaming a class rewrites no history.
     */
    public function name(): string;

    public DateTimeImmutable $occurredAt { get; }

    public Tenant $tenant { get; }

    public RequestContext $context { get; }
}
