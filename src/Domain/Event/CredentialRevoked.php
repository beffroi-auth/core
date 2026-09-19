<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Event;

use DateTimeImmutable;
use Heidi\Core\Domain\Credential\Credential;
use Heidi\Core\Domain\Credential\RevocableCredentialInterface;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;

/**
 * The credential never authenticates again; its row stays.
 */
final readonly class CredentialRevoked implements DomainEventInterface
{
    public Tenant $tenant;

    public function __construct(
        public Credential&RevocableCredentialInterface $credential,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
        $this->tenant = $credential->tenant;
    }
}
