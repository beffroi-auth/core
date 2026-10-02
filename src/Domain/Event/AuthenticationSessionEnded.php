<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Session\AuthenticationSession;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Session\SessionEnd;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\LogicException;

final readonly class AuthenticationSessionEnded implements DomainEventInterface
{
    public Tenant $tenant;

    public SessionEnd $endedBy;

    public function __construct(
        public AuthenticationSession $session,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
        $this->tenant = $session->tenant;
        $this->endedBy = $session->endedBy ?? throw new LogicException('The session has not ended.');
    }
}
