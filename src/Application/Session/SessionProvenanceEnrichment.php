<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Session;

use Beffroi\Core\Port\AuthenticationSessionRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final readonly class SessionProvenanceEnrichment
{
    public function __construct(
        private AuthenticationSessionRepositoryInterface $sessions,
        private ProvenancePlace $place,
    ) {
    }

    public function enrich(Uuid $sessionId): void
    {
        $session = $this->sessions->find($sessionId);
        if ($session === null) {
            return;
        }
        $createdFrom = $session->createdFrom;
        $lastSeenFrom = $session->lastSeenFrom;
        $createdIn = $this->place->of($createdFrom);
        $lastSeenIn = $lastSeenFrom->ipAddress === $createdFrom->ipAddress ? $createdIn : $this->place->of($lastSeenFrom);
        if ($createdIn === $createdFrom->location && $lastSeenIn === $lastSeenFrom->location) {
            return;
        }
        $session->locate($createdIn, $lastSeenIn);
        $this->sessions->save($session);
    }
}
