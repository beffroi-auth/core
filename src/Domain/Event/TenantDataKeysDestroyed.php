<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

final readonly class TenantDataKeysDestroyed implements DomainEventInterface
{
    public const string NAME = 'tenant.data_keys_destroyed';

    public function __construct(
        public Tenant $tenant,
        public int $keys,
        public int $scopes,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
    }

    public function name(): string
    {
        return self::NAME;
    }
}
