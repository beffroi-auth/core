<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Identity;

use DateTimeImmutable;
use Heidi\Core\Domain\Tenant\Tenant;
use function hash;
use Heidi\Core\Exception\InvalidArgumentException;
use function preg_match;
use function sprintf;
use function str_starts_with;
use Symfony\Component\Uid\Uuid;

/**
 * One browser of a user that this provider may push to (RFC 8030 §2, Push API §4): where the push service
 * takes a message for it, the two keys it gave to encrypt one (RFC 8291 §3.1), and the application server
 * key of the tenant it was made under, by its "kid".
 *
 * It is the user's and not a client's, so it lives with the rest of what a tenant knows about somebody
 * (decision 0002): several per user, because a user has several browsers, restated with the tenant of
 * that user, and taken out of use rather than deleted, so that a row says a browser was reachable and is
 * not any more. It proves nothing and is not a credential: nobody signs in with it.
 *
 * It is bound to the key it was made under. A browser subscribes with the public half of the application
 * server key it is handed (RFC 8292 §3.2), and the push service refuses a request signed by any other,
 * so a rotation of that key does not move a subscription: it goes on being pushed to until the key it
 * names retires, and the browser subscribes again under the new one (architecture §3.3).
 */
final class PushSubscription
{
    /**
     * Push API §4: what a browser calls the subscription is a URL of its push service, and nothing this
     * provider chose. It is taken as it was given, and only what could not be a push endpoint is refused.
     */
    private const string ENDPOINT_SCHEME = 'https://';

    /**
     * RFC 8291 §3.1, §4: the two keys arrive base64url, the public key of the browser and the secret both
     * sides derive from. Their lengths are the push service's business and not this provider's; their
     * alphabet is.
     */
    private const string BASE64URL_PATTERN = '/^[A-Za-z0-9_-]+$/D';

    /**
     * Restated from the user (decision 0002).
     */
    public readonly Tenant $tenant;

    /**
     * The hex SHA-256 of the endpoint: what a browser is recognised by, an endpoint being as long as its
     * push service decided and longer than a key may be.
     */
    public readonly string $endpointHash;

    public private(set) ?DateTimeImmutable $lastPushedAt = null;

    public private(set) ?DateTimeImmutable $revokedAt = null;

    /**
     * @param string $endpoint where the push service takes a message for this browser (RFC 8030 §2)
     * @param string $publicKey the "p256dh" key the browser gave, base64url (RFC 8291 §3.1)
     * @param string $authSecret the "auth" secret the browser gave, base64url (RFC 8291 §3.2)
     * @param string $applicationServerKid the key of the tenant this subscription is bound to
     * @param string|null $label what the user is shown to tell one of their browsers from another
     *
     * @throws InvalidArgumentException when it is not something a push service could be asked to deliver to
     */
    public function __construct(
        public private(set) Uuid $id,
        public readonly User $user,
        public readonly string $endpoint,
        public readonly string $publicKey,
        public readonly string $authSecret,
        public readonly string $applicationServerKid,
        public readonly ?string $label,
        public readonly DateTimeImmutable $createdAt,
    ) {
        if (! str_starts_with($endpoint, self::ENDPOINT_SCHEME)) {
            throw new InvalidArgumentException('A push subscription is delivered to over HTTPS (RFC 8030 section 2).');
        }
        foreach ([
            'public key' => $publicKey,
            'authentication secret' => $authSecret,
        ] as $name => $value) {
            if (preg_match(self::BASE64URL_PATTERN, $value) !== 1) {
                throw new InvalidArgumentException(sprintf('The %s of a push subscription is base64url (RFC 8291 section 3.1).', $name));
            }
        }
        if ($applicationServerKid === '') {
            throw new InvalidArgumentException('A push subscription names the application server key it was made under (RFC 8292 section 3.2).');
        }
        $this->tenant = $user->tenant;
        $this->endpointHash = self::digestOf($endpoint);
    }

    /**
     * What an endpoint is looked up by, written once so that a caller with an endpoint in hand and a row
     * already stored are compared the same way.
     */
    public static function digestOf(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }

    /**
     * The message left this provider for the push service. What became of it afterwards is between the
     * push service and the browser, and this provider never learns it.
     */
    public function markPushed(DateTimeImmutable $now): void
    {
        $this->lastPushedAt = $now;
    }

    /**
     * The user took this browser out, or the push service answered that the subscription is gone (RFC
     * 8030 §7.3: 404 and 410 are the same fact). The first one wins, and the row stays.
     */
    public function revoke(DateTimeImmutable $now): void
    {
        if ($this->revokedAt !== null) {
            return;
        }
        $this->revokedAt = $now;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    /**
     * Whether it is a subscription of that key: a subscription the key it names has outlived is pushed
     * to by nobody, the push service refusing a request signed by any other key (RFC 8292 §4.2).
     */
    public function isBoundTo(string $kid): bool
    {
        return $this->applicationServerKid === $kid;
    }
}
