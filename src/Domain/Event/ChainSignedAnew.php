<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * Decision 0060: a person read what the check of a chain found, and had it linked and signed again as the database
 * holds it.
 */
final readonly class ChainSignedAnew implements DomainEventInterface
{
    public const string NAME = 'tenant.chain_signed_anew';

    public function __construct(
        public Tenant $tenant,
        public string $chain,
        public int $from,
        public int $to,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
    }

    public function name(): string
    {
        return self::NAME;
    }
}
