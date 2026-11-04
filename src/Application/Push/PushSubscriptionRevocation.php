<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Push;

use Beffroi\Core\Domain\Identity\PushSubscription;
use Beffroi\Core\Port\PushSubscriptionRepositoryInterface;
use Psr\Clock\ClockInterface;

final readonly class PushSubscriptionRevocation
{
    public function __construct(
        private PushSubscriptionRepositoryInterface $subscriptions,
        private ClockInterface $clock,
    ) {
    }

    public function revoke(PushSubscription $subscription): void
    {
        if ($subscription->isRevoked()) {
            return;
        }
        $subscription->revoke($this->clock->now());
        $this->subscriptions->save($subscription);
    }
}
