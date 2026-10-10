<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Push;

use Beffroi\Core\Domain\Identity\PushSubscription;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\PushSubscriptionRepositoryInterface;

/**
 * The person whose browsers may be pushed to names one of them, and names it again (architecture §5.2):
 * what tells one browser from another on the page of their account is a name they wrote, so correcting it
 * is their own act and not an operator's.
 *
 * The counterpart of PushSubscriptionEnrolment and PushSubscriptionRevocation, and silent as both of them
 * are: a subscription proves nothing, nobody signs in with one, and a browser given another name is not an
 * act a journal is read to recognise.
 */
final readonly class PushSubscriptionNaming
{
    public function __construct(
        private PushSubscriptionRepositoryInterface $subscriptions,
    ) {
    }

    /**
     * A name that is the one it already holds changes nothing and writes nothing.
     *
     * @throws InvalidArgumentException when the label is not one a browser may be called
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
