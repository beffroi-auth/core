<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Credential\RevocableCredentialInterface;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

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
