<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Identity\PushSubscription;
use Beffroi\Core\Domain\Identity\User;
use function hash_equals;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;
use Beffroi\Core\Port\CanPageActiveSubscriptionsInterface;
use Beffroi\Core\Port\PushSubscriptionRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * The push subscriptions in memory, for the tests and for a deployment that keeps nothing.
 */
final class InMemoryPushSubscriptionRepository implements CanPageActiveSubscriptionsInterface, PushSubscriptionRepositoryInterface
{
    /**
     * @var array<string, PushSubscription> by identifier, in the order they were saved
     */
    private array $subscriptions = [];

    /**
     * @return Listing<PushSubscription>
     */
    public function pageOfActiveSubscriptions(User $user, Pagination $pagination): Listing
    {
        return Listing::of($this->findActiveByUser($user), $pagination);
    }

    public function findActiveByUser(User $user): array
    {
        $active = [];
        foreach ($this->subscriptions as $subscription) {
            if (! $subscription->isRevoked() && $subscription->user->id->equals($user->id)) {
                $active[] = $subscription;
            }
        }

        return $active;
    }

    public function findByEndpoint(User $user, string $endpoint): ?PushSubscription
    {
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->user->id->equals($user->id) && hash_equals($subscription->endpoint, $endpoint)) {
                return $subscription;
            }
        }

        return null;
    }

    public function find(Uuid $id): ?PushSubscription
    {
        return $this->subscriptions[$id->toRfc4122()] ?? null;
    }

    public function save(PushSubscription $subscription): void
    {
        $this->subscriptions[$subscription->id->toRfc4122()] = $subscription;
    }
}
