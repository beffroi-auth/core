<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Event;

use DateTimeImmutable;
use Heidi\Core\Domain\Credential\Credential;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;

/**
 * A user got a credential of some kind through an enrolment; the kind is the class of the credential.
 */
final readonly class CredentialEnrolled implements DomainEventInterface
{
    public Tenant $tenant;

    public function __construct(
        public Credential $credential,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
        $this->tenant = $credential->tenant;
    }
}
