<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Event;

use DateTimeImmutable;
use Heidi\Core\Domain\Key\SigningKey;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;

/**
 * A new key is published and signs nothing yet.
 */
final readonly class SigningKeyStaged implements DomainEventInterface
{
    public Tenant $tenant;

    public function __construct(
        public SigningKey $staged,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
        $this->tenant = $staged->tenant;
    }
}
