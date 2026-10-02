<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use Beffroi\Core\Domain\Event\AuthenticationFailed;
use Beffroi\Core\Domain\Event\AuthenticationFailureReason;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationMethod;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Refuses one authentication and says so; every refused authentication leaves the same trace, whatever
 * was tried (a password, a code, an assertion) and whatever the reason. The counterpart of the
 * SessionOpener, which announces the outcome the other way: what a login does is told in one place on
 * each side, so the audit trail cannot hold two shapes of the same fact.
 *
 * The reason is for the trail alone; what the user is told is the one message of
 * AuthenticationFailedException, the same for every reason (decision 0006).
 */
final readonly class AuthenticationRefusal
{
    public function __construct(
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * Announces the refusal and hands back what the caller throws, so that a refusal reads as one
     * statement at the place it happens.
     *
     * @param AuthenticationMethod|null $method what the attempt claimed, null when a ceremony refused before naming one
     * @param string|null $identifier kept as typed, for the trail; null when nothing was typed (a passkey)
     */
    public function refuse(
        Tenant $tenant,
        RequestContext $context,
        AuthenticationFailureReason $reason,
        ?AuthenticationMethod $method,
        ?string $identifier,
        ?User $user,
    ): AuthenticationFailedException {
        $this->events->dispatch(new AuthenticationFailed(
            $this->clock->now(),
            $tenant,
            $context,
            $method,
            $reason,
            $identifier,
            $user,
        ));

        return new AuthenticationFailedException($reason);
    }
}
