<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Event;

use DateTimeImmutable;
use Heidi\Core\Domain\Session\AuthenticationSession;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Session\SessionEnd;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\LogicException;

final readonly class AuthenticationSessionEnded implements DomainEventInterface
{
    public Tenant $tenant;

    public SessionEnd $endedBy;

    /**
     * @param RequestContext $context the request that ended the session; for an expiry sweep, the sweep's own context
     */
    public function __construct(
        public AuthenticationSession $session,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
        $this->tenant = $session->tenant;
        $this->endedBy = $session->endedBy ?? throw new LogicException('The session has not ended.');
    }
}
