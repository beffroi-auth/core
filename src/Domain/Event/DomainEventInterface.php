<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * Written to a broker: an event holds a domain object only when it serializes whole, and identifiers otherwise.
 */
interface DomainEventInterface
{
    /**
     * The label the journal writes down, stable whatever the class is called.
     */
    public function name(): string;

    public DateTimeImmutable $occurredAt { get; }

    public Tenant $tenant { get; }

    public RequestContext $context { get; }
}
