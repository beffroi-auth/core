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

    public function authenticatedAt(): DateTimeImmutable
    {
        return $this->proofs->latest();
    }

    public function authenticationContextClass(): AuthenticationContextClass
    {
        return $this->tenant->authenticationContextPolicy->classOf($this->proofs);
    }

    /**
     * OIDC Core §5.5.1.1
     */
    public function satisfies(AuthenticationContextClass $class): bool
    {
        return $class->isSatisfiedBy($this->proofs->methods());
    }

    /**
     * Dated at $provenAt when given, else when the request arrived, and never later than the request.
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
     * SAML Core §2.7.2
     */
    public function expireNoLaterThan(DateTimeImmutable $notOnOrAfter): void
    {
        if ($notOnOrAfter < $this->expiresAt) {
            $this->expiresAt = $notOnOrAfter;
        }
    }

    /**
     * Written on an ended session too: an enrichment that arrives after a logout is late, not wrong.
     */
    public function locate(?GeoLocation $createdIn, ?GeoLocation $lastSeenIn): void
    {
        $this->createdFrom = $this->createdFrom->withLocation($createdIn);
        $this->lastSeenFrom = $this->lastSeenFrom->withLocation($lastSeenIn);
    }

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
     * @return non-empty-list<string>
     */
    public function methodReferences(): array
    {
        return $this->proofs->methodReferences();
    }

    /**
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
