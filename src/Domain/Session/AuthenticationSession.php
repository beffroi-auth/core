<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use DateTimeImmutable;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\LogicException;
use Symfony\Component\Uid\Uuid;

/**
 * Who is authenticated at the provider, since when, how and from where. Tokens point at a session, which
 * makes them revocable together at logout. "How" is a map of proofs; "acr" is read from it, never stored.
 */
final class AuthenticationSession
{
    public private(set) AuthenticationProofs $proofs;

    public private(set) RequestContext $lastSeenFrom;

    public private(set) DateTimeImmutable $lastSeenAt;

    public private(set) ?DateTimeImmutable $endedAt = null;

    public private(set) ?SessionEnd $endedBy = null;

    public function __construct(
        public private(set) Uuid $id,
        public readonly Tenant $tenant,
        public readonly User $user,
        public readonly DateTimeImmutable $establishedAt,
        AuthenticationProofs $proofs,
        public private(set) RequestContext $createdFrom,
        public private(set) DateTimeImmutable $expiresAt,
    ) {
        if (! $user->belongsTo($tenant)) {
            throw new InvalidArgumentException('A session authenticates a user of its own tenant.');
        }
        if ($proofs->latest() > $establishedAt) {
            throw new InvalidArgumentException('No proof of a new session can be dated after the session was established.');
        }
        if ($expiresAt <= $establishedAt) {
            throw new InvalidArgumentException('A session expires after it was established.');
        }
        $this->proofs = $proofs;
        $this->lastSeenFrom = $createdFrom;
        $this->lastSeenAt = $establishedAt;
    }

    /**
     * The "auth_time" (OIDC Core §2): the most recent proof.
     */
    public function authenticatedAt(): DateTimeImmutable
    {
        return $this->proofs->latest();
    }

    /**
     * The "acr" (OIDC Core §2) when no class is requested: the first class of the policy of the tenant the
     * proofs satisfy (decision 0010).
     */
    public function authenticationContextClass(): AuthenticationContextClass
    {
        return $this->tenant->authenticationContextPolicy->classOf($this->proofs);
    }

    /**
     * OIDC Core §5.5.1.1: the requested class may be below the strongest one satisfied.
     */
    public function satisfies(AuthenticationContextClass $class): bool
    {
        return $class->isSatisfiedBy($this->proofs->methods());
    }

    /**
     * A re-authentication or a step-up: one more proof, dated now.
     */
    public function recordProof(AuthenticationMethod $method, RequestContext $context): void
    {
        if ($this->endedAt !== null) {
            throw new LogicException('An ended session cannot receive a proof.');
        }
        if ($context->receivedAt < $this->establishedAt) {
            throw new LogicException('A proof cannot predate the session.');
        }
        $this->proofs = $this->proofs->with($method, $context->receivedAt);
        $this->lastSeenFrom = $context;
        $this->lastSeenAt = $context->receivedAt;
    }

    /**
     * The places the enrichment found for the two provenances of this session (issue #473). It happens
     * after the request that opened the session and never in it (architecture §3.5), so this is the one
     * thing on a session that is written long after the fact, and it is written on an ended session too:
     * a message that arrives after a logout is late, not wrong.
     *
     * **It takes the places and not the provenances**: what a request carried, the address, the user agent
     * and the moment it reached this provider, is what reached this provider, and nothing that runs later
     * rewrites it.
     */
    public function locate(?GeoLocation $createdIn, ?GeoLocation $lastSeenIn): void
    {
        $this->createdFrom = $this->createdFrom->withLocation($createdIn);
        $this->lastSeenFrom = $this->lastSeenFrom->withLocation($lastSeenIn);
    }

    /**
     * The lifetime policy (sliding, absolute) belongs to the caller.
     */
    public function touch(RequestContext $context, DateTimeImmutable $expiresAt): void
    {
        if ($this->endedAt !== null) {
            throw new LogicException('An ended session cannot be touched.');
        }
        if ($expiresAt < $this->expiresAt) {
            throw new LogicException('Touching a session never shortens it; end it instead.');
        }
        $this->lastSeenFrom = $context;
        $this->lastSeenAt = $context->receivedAt;
        $this->expiresAt = $expiresAt;
    }

    /**
     * The first end wins.
     */
    public function end(SessionEnd $by, DateTimeImmutable $now): void
    {
        if ($this->endedAt !== null) {
            return;
        }
        $this->endedAt = $now;
        $this->endedBy = $by;
    }

    public function isActive(DateTimeImmutable $now): bool
    {
        return $this->endedAt === null && $now < $this->expiresAt;
    }

    public function hasEnded(): bool
    {
        return $this->endedAt !== null;
    }

    /**
     * @return non-empty-list<string> the "amr" claim values
     */
    public function methodReferences(): array
    {
        return $this->proofs->methodReferences();
    }
}
