<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Event;

use DateTimeImmutable;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;

/**
 * The KMS of the tenant did not answer or refused on the path of a request, which was not served: an
 * outage the audit sees as it happens, and not through the users it turned away. What it carries is what
 * failed and what the KMS said, never a secret, a key nor an envelope.
 */
final readonly class KmsUnavailable implements DomainEventInterface
{
    public function __construct(
        public DateTimeImmutable $occurredAt,
        public Tenant $tenant,
        public RequestContext $context,
        public string $reason,
    ) {
    }
}
