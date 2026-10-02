<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Push;

use Beffroi\Core\Domain\Identity\PushSubscription;
use Beffroi\Core\Port\PushSubscriptionRepositoryInterface;
use Psr\Clock\ClockInterface;

/**
 * A browser stops being pushed to, and its row stays (decision 0002): the user took it out on the page of
 * their account, or the push service answered that the subscription is gone (RFC 8030 §7.3), which is the
 * same fact learned from the other side. A second revocation changes nothing.
 *
 * The counterpart of PushSubscriptionEnrolment, and the one place either of the two callers writes it.
 */
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
