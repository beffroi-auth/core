<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Session;

use DateTimeImmutable;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\InvalidArgumentException;
use Heidi\Core\Exception\LogicException;
use Symfony\Component\Uid\Uuid;

/**
 * Who is authenticated at the provider, since when, how and from where. The integration owns the cookie;
 * the core owns this record and the decisions built on it. Tokens issued by a protocol point at a session,
 * which is what makes them revocable together at logout. Sessions end one by one.
 *
 * "How" is a map of proofs, not a single method and a single time: a re-authentication or a step-up adds or
 * refreshes one entry and leaves the others in place (see AuthenticationProofs).
 */
final class AuthenticationSession
{
    public private(set) AuthenticationProofs $proofs;

    public private(set) RequestContext $lastSeenFrom;

    public private(set) DateTimeImmutable $lastSeenAt;

    public private(set) ?DateTimeImmutable $endedAt = null;

    public private(set) ?SessionEnd $endedBy = null;

    /**
     * @param DateTimeImmutable $establishedAt when the session was opened, which is also the date of its first proof
     * @param string|null $authenticationContextClass the "acr" value, when the deployment defines classes
     */
    public function __construct(
        public private(set) Uuid $id,
        public readonly Tenant $tenant,
        public readonly User $user,
        public readonly DateTimeImmutable $establishedAt,
        AuthenticationProofs $proofs,
        public readonly ?string $authenticationContextClass,
        public readonly RequestContext $createdFrom,
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
        if ($authenticationContextClass === '') {
            throw new InvalidArgumentException('An authentication context class reference is either absent or non-empty.');
        }
        $this->proofs = $proofs;
        $this->lastSeenFrom = $createdFrom;
        $this->lastSeenAt = $establishedAt;
    }

    /**
     * The "auth_time" of OIDC Core §2: the moment of the most recent proof, which a re-authentication moves.
     */
    public function authenticatedAt(): DateTimeImmutable
    {
        return $this->proofs->latest();
    }

    /**
     * A re-authentication or a step-up: one more proof, dated now. Other proofs keep their own date.
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
     * Records activity: where the user was last seen from, and until when the session now lives. The
     * lifetime policy (sliding, absolute) belongs to the caller.
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
     * The first end wins: a session ended by the user and then swept by the expiry job stays "ended by user".
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
