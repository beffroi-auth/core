<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Session;

use Beffroi\Core\Port\AuthenticationSessionRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Says where a session was opened and where it was last seen (issue #473), after the request that wrote
 * the row and never in it: the enrichment of a provenance is asynchronous (architecture §3.5), so nothing
 * of a login waits on a database of addresses, and a session that was never enriched is a session whose
 * provenance says an address and no place.
 *
 * It takes the identifier and not the session: it runs on the lane, where the session the message named
 * is read again from the store rather than carried there.
 *
 * **It writes nothing when it found nothing.** A deployment that holds no database answers no place for
 * either provenance, and a row that would not change is a row this never touches, which is what lets the
 * enrichment be wired everywhere and cost nothing where it cannot answer.
 */
final readonly class SessionProvenanceEnrichment
{
    public function __construct(
        private AuthenticationSessionRepositoryInterface $sessions,
        private ProvenancePlace $place,
    ) {
    }

    /**
     * The session the message named, or nothing at all: a session that has been forgotten since is not an
     * error on this lane, it is a row that no longer needs a place.
     */
    public function enrich(Uuid $sessionId): void
    {
        $session = $this->sessions->find($sessionId);
        if ($session === null) {
            return;
        }
        $createdFrom = $session->createdFrom;
        $lastSeenFrom = $session->lastSeenFrom;
        $createdIn = $this->place->of($createdFrom);
        // Both provenances of a new session carry the one address the login came from, and a step-up
        // records another: the same address is looked up once, a second one is looked up too.
        $lastSeenIn = $lastSeenFrom->ipAddress === $createdFrom->ipAddress ? $createdIn : $this->place->of($lastSeenFrom);
        if ($createdIn === $createdFrom->location && $lastSeenIn === $lastSeenFrom->location) {
            return;
        }
        $session->locate($createdIn, $lastSeenIn);
        $this->sessions->save($session);
    }
}
