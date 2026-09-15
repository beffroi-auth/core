<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Session;

use function array_map;
use function array_unique;
use function array_values;
use DateTimeImmutable;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\InvalidArgumentException;
use Heidi\Core\Exception\LogicException;
use const SORT_REGULAR;
use Symfony\Component\Uid\Uuid;

/**
 * Who is authenticated at the provider, since when, how and from where. The integration owns the cookie;
 * the core owns this record and the decisions built on it. Tokens issued by a protocol point at a session,
 * which is what makes them revocable together at logout. Sessions end one by one.
 */
final class AuthenticationSession
{
    /**
     * @var non-empty-list<AuthenticationMethod>
     */
    public readonly array $methods;

    public private(set) RequestContext $lastSeenFrom;

    public private(set) DateTimeImmutable $lastSeenAt;

    public private(set) ?DateTimeImmutable $endedAt = null;

    public private(set) ?SessionEnd $endedBy = null;

    /**
     * @param list<AuthenticationMethod> $methods RFC 8176 values, at least one
     * @param string|null $authenticationContextClass the "acr" value, when the deployment defines classes
     */
    public function __construct(
        public readonly Uuid $id,
        public readonly Tenant $tenant,
        public readonly User $user,
        public readonly DateTimeImmutable $authenticatedAt,
        array $methods,
        public readonly ?string $authenticationContextClass,
        public readonly RequestContext $createdFrom,
        public private(set) DateTimeImmutable $expiresAt,
    ) {
        if (! $user->belongsTo($tenant)) {
            throw new InvalidArgumentException('A session authenticates a user of its own tenant.');
        }
        $methods = array_values(array_unique($methods, SORT_REGULAR));
        if ($methods === []) {
            throw new InvalidArgumentException('A session records at least one authentication method (RFC 8176).');
        }
        if ($expiresAt <= $authenticatedAt) {
            throw new InvalidArgumentException('A session expires after it was authenticated.');
        }
        if ($authenticationContextClass === '') {
            throw new InvalidArgumentException('An authentication context class reference is either absent or non-empty.');
        }
        $this->methods = $methods;
        $this->lastSeenFrom = $createdFrom;
        $this->lastSeenAt = $authenticatedAt;
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
        return array_map(static fn (AuthenticationMethod $method): string => $method->value, $this->methods);
    }
}
