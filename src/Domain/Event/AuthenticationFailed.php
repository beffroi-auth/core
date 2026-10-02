<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationMethod;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

final readonly class AuthenticationFailed implements DomainEventInterface
{
    /**
     * @param AuthenticationMethod|null $method what the attempt claimed, null when a ceremony refused a passkey before naming one
     * @param string|null $identifier kept as typed, for the audit trail; null when nothing was typed (a passkey)
     */
    public function __construct(
        public DateTimeImmutable $occurredAt,
        public Tenant $tenant,
        public RequestContext $context,
        public ?AuthenticationMethod $method,
        public AuthenticationFailureReason $reason,
        public ?string $identifier,
        public ?User $user,
    ) {
    }
}
