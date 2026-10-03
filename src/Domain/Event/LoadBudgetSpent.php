<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * A request was refused because a budget that bounds a load is spent (decision 0014): the audit sees
 * the refusal as it happens, and not through the callers it turned away. What it carries is the budget
 * that refused, the route that was asked for and the client when one was authenticated; the origin of
 * the request is in the context every event carries (architecture §3.5). No secret, no assertion, no
 * token, and nothing of what the request was asking for.
 */
final readonly class LoadBudgetSpent implements DomainEventInterface
{
    /**
     * The name of the act, which is what the journal writes down (issue #454).
     */
    public const string NAME = 'load.budget_spent';

    /**
     * @param string $budget the budget that refused, which names itself
     * @param string $route the route the request had been matched to
     * @param string|null $client the client the budget was counted on, null for the budget of an origin
     */
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
