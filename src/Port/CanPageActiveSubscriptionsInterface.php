<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\PushSubscription;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;
use Beffroi\Core\Domain\Listing\Period;

interface CanPageActiveSubscriptionsInterface
{
    /**
     * @param Period|null $period when they subscribed
     *
     * @return Listing<PushSubscription> the oldest first
     */
    public function pageOfActiveSubscriptions(User $user, Pagination $pagination, ?Period $period = null): Listing;
}
