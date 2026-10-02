<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Session\AuthenticationSession;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

final readonly class UserAuthenticated implements DomainEventInterface
{
    public DateTimeImmutable $occurredAt;

    public Tenant $tenant;

    public RequestContext $context;

    public function __construct(
        public AuthenticationSession $session,
    ) {
        $this->occurredAt = $session->establishedAt;
        $this->tenant = $session->tenant;
        $this->context = $session->createdFrom;
    }
}
