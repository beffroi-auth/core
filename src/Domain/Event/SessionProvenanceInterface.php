<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Symfony\Component\Uid\Uuid;

/**
 * An event whose provenance a session kept (issue #473): the row of that session holds the very address
 * this event carries, so the place found for it is written back on that row.
 *
 * **Naming a session is not saying this.** What a logout carried is the provenance of the logout and not
 * of the session it ended, so an event that merely holds a session does not implement this; what does is
 * an event raised where a session wrote down where it was seen. An event that comes to mean the same says
 * so by adding this interface, and nothing that enriches a provenance is edited.
 */
interface SessionProvenanceInterface extends DomainEventInterface
{
    public function sessionId(): Uuid;
}
