<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\PushSubscription;
use Beffroi\Core\Domain\Identity\User;
use Symfony\Component\Uid\Uuid;

interface PushSubscriptionRepositoryInterface
{
    /**
     * @return list<PushSubscription> the oldest first
     */
    public function findActiveByUser(User $user): array;

    /**
     * Revoked or not: a browser that subscribes again hands back the endpoint it already had.
     */
    public function findByEndpoint(User $user, string $endpoint): ?PushSubscription;

    public function find(Uuid $id): ?PushSubscription;

    public function save(PushSubscription $subscription): void;
}
