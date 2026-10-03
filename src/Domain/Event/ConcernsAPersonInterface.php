<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;

/**
 * An event that concerns a person, which is what lets the journal say whose act a row is (issue #454).
 *
 * The subject and not the user: it is the identifier that names a person without saying anything about
 * them, which is why a row of the journal holds it in the clear where what the request carried of that
 * person is sealed (decision 0011).
 *
 * It is absent where the act names nobody: an authentication that failed on an identifier no account
 * answers happened, and happened to no one this provider knows.
 *
 * A method, where the client of ConcernsAClientInterface is a property: an event holds a client
 * identifier, and reads a subject from the session or the credential it already carries, which a hooked
 * property cannot do on a readonly class. An event that comes to concern a person says so by adding this
 * interface, and nothing that writes the journal is edited.
 */
interface ConcernsAPersonInterface extends DomainEventInterface
{
    public function subject(): ?Subject;
}
