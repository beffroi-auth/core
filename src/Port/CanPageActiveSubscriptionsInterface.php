<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\PushSubscription;
use Beffroi\Core\Domain\Identity\PushSubscriptionQuestion;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;

interface CanPageActiveSubscriptionsInterface
{
    /**
     * @return Listing<PushSubscription> the oldest first
     */
    public function pageOfActiveSubscriptions(User $user, PushSubscriptionQuestion $question, Pagination $pagination): Listing;
}
