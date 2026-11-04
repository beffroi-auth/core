<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * Carries no secret, no assertion, no token, and nothing of what the request asked for.
 */
final readonly class LoadBudgetSpent implements DomainEventInterface
{
    public const string NAME = 'load.budget_spent';

    public function __construct(
        public DateTimeImmutable $occurredAt,
        public Tenant $tenant,
        public RequestContext $context,
        public string $budget,
        public string $route,
        public ?string $client,
    ) {
    }

    public function name(): string
    {
        return self::NAME;
    }
}
