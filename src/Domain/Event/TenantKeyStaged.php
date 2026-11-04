<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

final readonly class TenantKeyStaged implements DomainEventInterface
{
    public const string NAME = 'tenant.key_staged';

    public Tenant $tenant;

    public function __construct(
        public TenantKey $staged,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
        $this->tenant = $staged->tenant;
    }

    public function name(): string
    {
        return self::NAME;
    }
}
