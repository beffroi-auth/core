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
 * A browser of a user says where it can be pushed to (Push API §3.4, §8): what it hands over is recorded
 * against the application server key it was handed, so that what signs a push later is the key that
 * browser bound itself to (RFC 8292 §3.2).
 *
 * A browser that subscribes again hands back the endpoint it already had. That is one browser and not
 * two, so the row it already has is what changes: an endpoint the user had taken out comes back in use,
 * and one made under a key that has since been rotated is replaced rather than kept beside the new one.
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
     * @param string $endpoint where the push service takes a message for this browser
     * @param string $publicKey the "p256dh" key of the browser, base64url (RFC 8291 §3.1)
     * @param string $authSecret the "auth" secret of the browser, base64url (§3.2)
     * @param string|null $label what the user is shown to tell this browser from another of theirs
     *
     * @throws InvalidArgumentException when it is not something a push service could be asked to deliver to
     * @throws KeyNotFoundException when the tenant has no active application server key: a browser is
     *                              handed one before it subscribes, so there is nothing to record against
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
            // the row it has is what changes, and it is the row itself that changes: a second object over
            // one identity is not a second browser, and a store that holds the first one refuses it
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
