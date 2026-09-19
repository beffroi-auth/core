<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Event;

use DateTimeImmutable;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Session\AuthenticationMethod;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;

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
