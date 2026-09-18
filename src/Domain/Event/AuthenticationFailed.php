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
     * @param string $identifier kept as typed, for the audit trail
     */
    public function __construct(
        public DateTimeImmutable $occurredAt,
        public Tenant $tenant,
        public RequestContext $context,
        public AuthenticationMethod $method,
        public AuthenticationFailureReason $reason,
        public string $identifier,
        public ?User $user,
    ) {
    }
}
