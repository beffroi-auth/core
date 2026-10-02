<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Credential\PasswordCredential;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * The hash was stored again with the current policy; the password did not change.
 */
final readonly class PasswordRehashed implements DomainEventInterface
{
    public Tenant $tenant;

    public function __construct(
        public PasswordCredential $credential,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
        $this->tenant = $credential->tenant;
    }
}
