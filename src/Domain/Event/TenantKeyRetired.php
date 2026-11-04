<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Key\RetirementReason;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

final readonly class TenantKeyRetired implements DomainEventInterface
{
    public const string NAME = 'tenant.key_retired';

    public Tenant $tenant;

    public function __construct(
        public TenantKey $retired,
        public RetirementReason $reason,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
        $this->tenant = $retired->tenant;
    }

    public function name(): string
    {
        return self::NAME;
    }
}
