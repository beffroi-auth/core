<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Symfony\Component\Uid\Uuid;

/**
 * An event raised where its session was seen: the place found for its address is written back on that session.
 */
interface SessionProvenanceInterface extends DomainEventInterface
{
    public function sessionId(): Uuid;
}
