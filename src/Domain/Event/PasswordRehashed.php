<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Event;

use DateTimeImmutable;
use Heidi\Core\Domain\Credential\PasswordCredential;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;

/**
 * A password was verified against a hash weaker than the current policy and stored again with the current
 * one. The password did not change.
 */
final readonly class PasswordRehashed implements DomainEventInterface
{
    public Tenant $tenant;

    public function __construct(
        public PasswordCredential $credential,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
        $this->tenant = $credential->user->tenant;
    }
}
