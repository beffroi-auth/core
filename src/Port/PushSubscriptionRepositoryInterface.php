<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\PushSubscription;
use Beffroi\Core\Domain\Identity\User;
use Symfony\Component\Uid\Uuid;

/**
 * The browsers of a user this provider may push to (decision 0002). A subscription is recognised by its
 * endpoint, which is what a browser hands back unchanged when it subscribes again, and a user has several
 * of them at once.
 */
interface PushSubscriptionRepositoryInterface
{
    /**
     * The ones still in use, oldest first; a revoked one is reached by nobody and is not among them.
     *
     * @return list<PushSubscription>
     */
    public function findActiveByUser(User $user): array;

    /**
     * The subscription of that user at that endpoint, revoked or not: a browser that subscribes again
     * hands back the endpoint it already had, and two rows for one browser would be two pushes for one
     * notification.
     */
    public function findByEndpoint(User $user, string $endpoint): ?PushSubscription;

    public function find(Uuid $id): ?PushSubscription;

    public function save(PushSubscription $subscription): void;
}
