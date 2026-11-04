<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * Never carries a secret, a key nor an envelope.
 */
final readonly class KmsUnavailable implements DomainEventInterface
{
    public const string NAME = 'kms.unavailable';

    public function __construct(
        public DateTimeImmutable $occurredAt,
        public Tenant $tenant,
        public RequestContext $context,
        public string $reason,
    ) {
    }

    public function name(): string
    {
        return self::NAME;
    }
}
