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
use function array_filter;
use function array_slice;
use Psr\Clock\ClockInterface;
use function usort;

/**
 * Push API §3.4, §8, RFC 8292 §3.2
 */
final readonly class PushSubscriptionEnrolment
{
    /**
     * Every browser of a person is pushed at each notice, one after another, each for as long as its push service
     * takes: a person keeps the browsers they subscribed last, never so many that they hold the lane of notices.
     */
    public const int MOST_BROWSERS = 10;

    public function __construct(
        private PushSubscriptionRepositoryInterface $subscriptions,
        private ApplicationServerKeyProviderInterface $keys,
        private UuidGeneratorInterface $uuids,
        private ClockInterface $clock,
        private PushSubscriptionRevocation $revocation,
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
            $this->takeOutTheOldestBeside($existing);

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
        $this->takeOutTheOldestBeside($subscription);

        return $subscription;
    }

    private function takeOutTheOldestBeside(PushSubscription $enrolled): void
    {
        $others = array_filter(
            $this->subscriptions->findActiveByUser($enrolled->user),
            static fn (PushSubscription $other): bool => ! $other->id->equals($enrolled->id),
        );
        usort($others, static fn (PushSubscription $one, PushSubscription $other): int => $other->createdAt <=> $one->createdAt);
        foreach (array_slice($others, self::MOST_BROWSERS - 1) as $oldest) {
            $this->revocation->revoke($oldest);
        }
    }
}
