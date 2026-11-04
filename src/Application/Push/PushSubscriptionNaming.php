<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Push;

use Beffroi\Core\Domain\Identity\PushSubscription;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\PushSubscriptionRepositoryInterface;

final readonly class PushSubscriptionNaming
{
    public function __construct(
        private PushSubscriptionRepositoryInterface $subscriptions,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function rename(PushSubscription $subscription, string $label): void
    {
        $was = $subscription->label;
        $subscription->rename($label);
        if ($subscription->label === $was) {
            return;
        }
        $this->subscriptions->save($subscription);
    }
}
