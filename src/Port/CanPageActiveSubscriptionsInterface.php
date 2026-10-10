<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\PushSubscription;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;

/**
 * A companion of PushSubscriptionRepositoryInterface: one page of the browsers of a person this provider
 * may push to, for the table of their account that takes one out (architecture §5.6).
 */
interface CanPageActiveSubscriptionsInterface
{
    /**
     * @return Listing<PushSubscription> the oldest first, as the list of them is
     */
    public function pageOfActiveSubscriptions(User $user, Pagination $pagination): Listing;
}
