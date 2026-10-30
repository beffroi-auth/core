<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use Beffroi\Core\Domain\PersonalData\ClassifiedFieldsInterface;
use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use Beffroi\Core\Domain\PersonalData\Sealed;
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
final class AuthenticationSession implements ClassifiedFieldsInterface
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
     * A re-authentication or a step-up: one more proof, dated when the request arrived, or at $provenAt when what
     * proved it dates it (the AuthnInstant of an upstream identity provider, beffroi/saml S-57), never later than the
     * request; the newest proof of a method is kept (AuthenticationProofs::with()).
     */
    public function recordProof(AuthenticationMethod $method, RequestContext $context, ?DateTimeImmutable $provenAt = null): void
    {
        if ($this->endedAt !== null) {
            throw new LogicException('An ended session cannot receive a proof.');
        }
        if ($context->receivedAt < $this->establishedAt) {
            throw new LogicException('The request that carries a proof cannot predate the session.');
        }
        $this->proofs = $this->proofs->with($method, $provenAt === null || $provenAt > $context->receivedAt ? $context->receivedAt : $provenAt);
        $this->lastSeenFrom = $context;
        $this->lastSeenAt = $context->receivedAt;
    }

    /**
     * The instant an upstream identity provider allows the session it vouches for (the SessionNotOnOrAfter of SAML
     * Core §2.7.2, beffroi/saml S-57): the session expires no later than that, and a bound later than its expiry
     * changes nothing, so that the provider shortens a session and never lengthens one. Reaching it ends nothing
     * (decision 0031).
     */
    public function expireNoLaterThan(DateTimeImmutable $notOnOrAfter): void
    {
        if ($notOnOrAfter < $this->expiresAt) {
            $this->expiresAt = $notOnOrAfter;
        }
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

    /**
     * The two provenances of a session are sealed under its own family, each holding the address, the user agent,
     * the place and the device of a moment in one field; what the session proved stays in the clear, every token
     * issued from it carrying the same.
     *
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            'establishedAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'proofs' => new NoPersonalDatum(Reason::WHAT_THE_SESSION_PROVED),
            'createdFrom' => new Sealed('authentication_session'),
            'lastSeenFrom' => new Sealed('authentication_session'),
            'lastSeenAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'expiresAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'endedAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'endedBy' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'id' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
            'tenant' => new NoPersonalDatum(Reason::THE_TENANT),
            'user' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
        ];
    }
}
