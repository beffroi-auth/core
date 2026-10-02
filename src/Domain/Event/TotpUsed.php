<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * A code of the TOTP credential was accepted: one the authenticator computed, or one of the backup codes,
 * which is told here because a backup code spent is a code the user no longer has.
 */
final readonly class TotpUsed implements DomainEventInterface
{
    public Tenant $tenant;

    public function __construct(
        public TotpCredential $credential,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
        public bool $backupCode,
    ) {
        $this->tenant = $credential->tenant;
    }
}
