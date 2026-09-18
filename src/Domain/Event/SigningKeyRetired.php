<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Event;

use DateTimeImmutable;
use Heidi\Core\Domain\Key\RetirementReason;
use Heidi\Core\Domain\Key\SigningKey;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;

/**
 * A key left the JWK Set: every JWS naming its "kid" is refused from now on.
 */
final readonly class SigningKeyRetired implements DomainEventInterface
{
    public Tenant $tenant;

    public function __construct(
        public SigningKey $retired,
        public RetirementReason $reason,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
        $this->tenant = $retired->tenant;
    }
}
