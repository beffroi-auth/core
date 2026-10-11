<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use function array_any;
use Beffroi\Core\Exception\InvalidArgumentException;
use function preg_match;
use function sprintf;
use Stringable;

/**
 * One class of the policy of a tenant (decision 0010): a name, the value of the "acr" claim (OIDC Core §2),
 * on one of the predicates of the provider. Never stored on a session: a session satisfies every class
 * whose predicate holds on its proofs (architecture §3.2). The constants are the names of the default
 * policy, the Keycloak levels and the two names of OpenID Connect EAP ACR Values 1.0.
 *
 * What a name may mean is refused here, wherever a class is built and not only where a policy holds one
 * (decision 0006): a name that is not printable ASCII without space, and "phr" or "phrh" on a predicate
 * that is not their meaning (OIDC Core §2 forbids a registered name in another sense).
 */
final readonly class AuthenticationContextClass implements Stringable
{
    public const string SINGLE_FACTOR = '1';

    public const string TWO_FACTORS = '2';

    public const string PHISHING_RESISTANT = 'phr';

    public const string PHISHING_RESISTANT_HARDWARE = 'phrh';

    /**
     * OIDC Core §2: printable ASCII without space.
     */
    private const string PATTERN = '/^[\x21-\x7E]{1,255}$/D';

    public function __construct(
        public string $value,
        public AuthenticationPredicate $predicate,
    ) {
        if (! self::isWellFormed($value)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a well-formed authentication context class reference.', $value));
        }
        self::assertRegisteredMeaning($value, $predicate);
    }

    public static function singleFactor(): self
    {
        return new self(self::SINGLE_FACTOR, AuthenticationPredicate::SingleFactor);
    }

    public static function twoFactors(): self
    {
        return new self(self::TWO_FACTORS, AuthenticationPredicate::TwoFactors);
    }

    public static function phishingResistant(): self
    {
        return new self(self::PHISHING_RESISTANT, AuthenticationPredicate::PhishingResistant);
    }

    public static function phishingResistantHardware(): self
    {
        return new self(self::PHISHING_RESISTANT_HARDWARE, AuthenticationPredicate::PhishingResistantHardware);
    }

    public static function isWellFormed(string $value): bool
    {
        return preg_match(self::PATTERN, $value) === 1;
    }

    /**
     * @param list<AuthenticationMethod> $methods what was proven, or what a login would prove
     */
    public function isSatisfiedBy(array $methods): bool
    {
        return $this->predicate->isSatisfiedBy($methods);
    }

    /**
     * Two classes are the same when they carry the same name: a policy names each once.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * OIDC Core §2: a registered name keeps its meaning. "phr" is any phishing-resistant proof, in
     * hardware or not; "phrh" is the hardware one only.
     */
    private static function assertRegisteredMeaning(string $value, AuthenticationPredicate $predicate): void
    {
        $allowed = match ($value) {
            self::PHISHING_RESISTANT => [AuthenticationPredicate::PhishingResistant, AuthenticationPredicate::PhishingResistantHardware],
            self::PHISHING_RESISTANT_HARDWARE => [AuthenticationPredicate::PhishingResistantHardware],
            default => null,
        };
        if ($allowed !== null && ! array_any($allowed, static fn (AuthenticationPredicate $meaning): bool => $meaning === $predicate)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is a registered name (OpenID Connect EAP ACR Values 1.0) and keeps its meaning: it cannot be named on the "%s" predicate.',
                $value,
                $predicate->value,
            ));
        }
    }
}
