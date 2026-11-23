<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use Beffroi\Core\Domain\Listing\Period;

/**
 * What a list of push subscriptions is narrowed to, a criterion left null admitting every subscription.
 */
final readonly class PushSubscriptionQuestion
{
    /**
     * @param Period|null $pushed a subscription nothing was pushed to being in no period
     */
    public function __construct(
        public ?Period $subscribed = null,
        public ?Period $pushed = null,
    ) {
    }

    public function admits(PushSubscription $subscription): bool
    {
        return (
            $this->subscribed?->holds($subscription->createdAt) !== false
            && ($this->pushed === null || $subscription->lastPushedAt !== null && $this->pushed->holds($subscription->lastPushedAt))
        );
    }
}
