<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Push;

use Beffroi\Core\Domain\Identity\PushSubscription;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Key\ApplicationServerAlgorithmInterface;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Port\ApplicationServerKeyProviderInterface;
use Beffroi\Core\Port\PushSubscriptionRepositoryInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use Psr\Clock\ClockInterface;

/**
 * Push API §3.4, §8, RFC 8292 §3.2
 */
final readonly class PushSubscriptionEnrolment
{
    public function __construct(
        private PushSubscriptionRepositoryInterface $subscriptions,
        private ApplicationServerKeyProviderInterface $keys,
        private UuidGeneratorInterface $uuids,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param string $publicKey the "p256dh" key of the browser, base64url (RFC 8291 §3.1)
     * @param string $authSecret the "auth" secret of the browser, base64url (RFC 8291 §3.2)
     *
     * @throws InvalidArgumentException
     * @throws KeyNotFoundException when the tenant has no active application server key
     */
    public function enrol(
        User $user,
        ApplicationServerAlgorithmInterface $algorithm,
        string $endpoint,
        string $publicKey,
        string $authSecret,
        ?string $label,
    ): PushSubscription {
        $kid = $this->keys->activeApplicationServerKey($user->tenant, $algorithm)->kid;
        $existing = $this->subscriptions->findByEndpoint($user, $endpoint);
        if ($existing !== null) {
            // The row itself changes: a store that holds it refuses a second object over the same identity.
            $existing->resubscribedWith($publicKey, $authSecret, $kid, $label);
            $this->subscriptions->save($existing);

            return $existing;
        }
        $subscription = new PushSubscription(
            $this->uuids->timeOrdered(),
            $user,
            $endpoint,
            $publicKey,
            $authSecret,
            $kid,
            $label,
            $this->clock->now(),
        );
        $this->subscriptions->save($subscription);

        return $subscription;
    }
}
