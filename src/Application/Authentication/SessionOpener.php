<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use DateInterval;
use DateTimeImmutable;
use Beffroi\Core\Domain\Event\AuthenticationFailureReason;
use Beffroi\Core\Domain\Event\UserAuthenticated;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationMethod;
use Beffroi\Core\Domain\Session\AuthenticationProofs;
use Beffroi\Core\Domain\Session\AuthenticationSession;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\AuthenticationSessionRepositoryInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Opens one session on what was just proven and says so; every opened session leaves the same trace,
 * whatever proved it (a password, a passkey, an upstream provider). The counterpart of SessionTerminator.
 * The lifetime policy is the one place it lives; how a session is extended is the caller's (touch()).
 *
 * **It is where the floor of the tenant holds** (issue #327): every login of this provider ends here, so a
 * session that does not satisfy the predicate the tenant requires is refused here and in no second place.
 * A login that proves too little for its tenant is refused and not held: what one more proof would reach
 * is a step-up, decided against a request (decision 0010), where this is what the tenant requires of
 * everybody whoever asked.
 */
final readonly class SessionOpener
{
    public function __construct(
        private AuthenticationSessionRepositoryInterface $sessions,
        private UuidGeneratorInterface $uuids,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
        private AuthenticationRefusal $refusals,
        private DateInterval $lifetime = new DateInterval('PT12H'),
    ) {
    }

    /**
     * @param non-empty-list<AuthenticationMethod> $methods what was proven, every one dated now, or at $provenAt
     * @param DateTimeImmutable|null $provenAt when what proved them dates it, the AuthnInstant of an upstream identity
     *                                         provider (beffroi/saml S-57); never later than now, a clock ahead of
     *                                         ours within its skew dated by ours, no proof being later than the
     *                                         session that holds it
     * @param DateTimeImmutable|null $notOnOrAfter when what proved them allows the session to expire at the latest,
     *                                             the SessionNotOnOrAfter of an upstream identity provider
     *                                             (beffroi/saml S-57); the session expires at the earlier of its
     *                                             lifetime and that instant
     *
     * @throws AuthenticationFailedException when what was proven is under the floor of the tenant
     */
    public function open(
        Tenant $tenant,
        User $user,
        array $methods,
        RequestContext $context,
        ?DateTimeImmutable $provenAt = null,
        ?DateTimeImmutable $notOnOrAfter = null,
    ): AuthenticationSession {
        if (! $tenant->loginFloor->isSatisfiedBy($methods)) {
            throw $this->refusals->refuse($tenant, $context, AuthenticationFailureReason::BelowTheFloor, $methods[0], null, $user);
        }
        $now = $this->clock->now();
        $expiresAt = $now->add($this->lifetime);
        $session = new AuthenticationSession(
            $this->uuids->timeOrdered(),
            $tenant,
            $user,
            $now,
            AuthenticationProofs::ofAll($methods, $provenAt === null || $provenAt > $now ? $now : $provenAt),
            $context,
            $notOnOrAfter !== null && $notOnOrAfter < $expiresAt ? $notOnOrAfter : $expiresAt,
        );
        $this->sessions->save($session);
        $this->events->dispatch(new UserAuthenticated($session));

        return $session;
    }
}
