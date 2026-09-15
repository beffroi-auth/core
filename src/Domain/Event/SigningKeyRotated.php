<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Event;

use DateTimeImmutable;
use Heidi\Core\Domain\Key\SigningKey;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;

/**
 * A new key signs from now on; the previous active one, if any, entered its rotation window.
 */
final readonly class SigningKeyRotated implements DomainEventInterface
{
    public Tenant $tenant;

    public function __construct(
        public SigningKey $activated,
        public ?SigningKey $retiring,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
        $this->tenant = $activated->tenant;
    }
}
