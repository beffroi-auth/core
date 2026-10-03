<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * A new key signs from now on; the previous active one, if any, entered its retirement window.
 */
final readonly class TenantKeyRotated implements DomainEventInterface
{
    /**
     * The name of the act, which is what the journal writes down (issue #454).
     */
    public const string NAME = 'tenant.key_rotated';

    public Tenant $tenant;

    public function __construct(
        public TenantKey $activated,
        public ?TenantKey $retiring,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
        $this->tenant = $activated->tenant;
    }

    public function name(): string
    {
        return self::NAME;
    }
}
