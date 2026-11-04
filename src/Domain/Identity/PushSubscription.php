<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use Beffroi\Core\Domain\PersonalData\ClassifiedFieldsInterface;
use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use Beffroi\Core\Domain\PersonalData\Sealed;
use DateTimeImmutable;
use Beffroi\Core\Domain\Tenant\Tenant;
use function hash;
use Beffroi\Core\Exception\InvalidArgumentException;
use function mb_strlen;
use function preg_match;
use function sprintf;
use function str_starts_with;
use function trim;
use Symfony\Component\Uid\Uuid;

/**
 * RFC 8030 §2, Push API §3.4
 *
 * Bound to the application server key it was made under: the push service refuses any other (RFC 8292 §3.2).
 */
final class PushSubscription implements ClassifiedFieldsInterface
{
    private const string ENDPOINT_SCHEME = 'https://';

    /**
     * The alphabet only: the lengths of the keys are the push service's business.
     */
    private const string BASE64URL_PATTERN = '/^[A-Za-z0-9_-]+$/D';

    public const int LABEL_MAX_LENGTH = 255;

    public readonly Tenant $tenant;

    /**
     * A browser is recognised by this hash, its endpoint being longer than a key may be.
     */
    public readonly string $endpointHash;

    public private(set) ?DateTimeImmutable $lastPushedAt = null;

    public private(set) ?DateTimeImmutable $revokedAt = null;

    /**
     * @param string $publicKey the "p256dh" key, base64url (RFC 8291 §3.1)
     * @param string $authSecret the "auth" secret, base64url (RFC 8291 §3.2)
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public private(set) Uuid $id,
        public readonly User $user,
        public readonly string $endpoint,
        public private(set) string $publicKey,
        public private(set) string $authSecret,
        public private(set) string $applicationServerKid,
        public private(set) ?string $label,
        public readonly DateTimeImmutable $createdAt,
    ) {
        if (! str_starts_with($endpoint, self::ENDPOINT_SCHEME)) {
            throw new InvalidArgumentException('A push subscription is delivered to over HTTPS (RFC 8030 section 2).');
        }
        self::assertWhatABrowserHandsOver($publicKey, $authSecret, $applicationServerKid);
        $this->tenant = $user->tenant;
        $this->endpointHash = self::digestOf($endpoint);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function resubscribedWith(string $publicKey, string $authSecret, string $applicationServerKid, ?string $label): void
    {
        self::assertWhatABrowserHandsOver($publicKey, $authSecret, $applicationServerKid);
        $this->publicKey = $publicKey;
        $this->authSecret = $authSecret;
        $this->applicationServerKid = $applicationServerKid;
        $this->label = $label;
        $this->revokedAt = null;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function rename(string $label): void
    {
        $label = trim($label);
        if ($label === '' || mb_strlen($label) > self::LABEL_MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('The name of a browser is 1 to %d characters long.', self::LABEL_MAX_LENGTH));
        }
        $this->label = $label;
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function assertWhatABrowserHandsOver(string $publicKey, string $authSecret, string $applicationServerKid): void
    {
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
    }

    public static function digestOf(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }

    public function markPushed(DateTimeImmutable $now): void
    {
        $this->lastPushedAt = $now;
    }

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

    public function isBoundTo(string $kid): bool
    {
        return $this->applicationServerKid === $kid;
    }

    /**
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            'endpoint' => new Sealed('push_subscription'),
            'endpointHash' => new NoPersonalDatum(Reason::HASHED),
            'publicKey' => new NoPersonalDatum(Reason::A_KEY_OF_THE_SUBSCRIPTION),
            'authSecret' => new NoPersonalDatum(Reason::A_KEY_OF_THE_SUBSCRIPTION),
            'applicationServerKid' => new NoPersonalDatum('names the key of the tenant a push to this browser is signed with'),
            'label' => new Sealed('push_subscription'),
            'createdAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'lastPushedAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'revokedAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'id' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
            'tenant' => new NoPersonalDatum(Reason::THE_TENANT),
            'user' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
        ];
    }
}
