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

final readonly class AuthenticationRefusal
{
    public function __construct(
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

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
