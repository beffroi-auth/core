<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Credential\PasskeyCredential;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * An assertion of the passkey was verified and a session opened on it. A sign count that did not grow
 * (WebAuthn Level 3 §6.1.1) is told here, not refused: a synced passkey keeps it at zero.
 */
final readonly class PasskeyUsed implements DomainEventInterface
{
    public Tenant $tenant;

    public function __construct(
        public PasskeyCredential $credential,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
        public bool $signCountRegressed,
    ) {
        $this->tenant = $credential->tenant;
    }
}
