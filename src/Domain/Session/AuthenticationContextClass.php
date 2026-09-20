<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Session;

use Heidi\Core\Exception\InvalidArgumentException;
use function preg_match;
use function sprintf;
use Stringable;

/**
 * One class of the policy of a tenant (decision 0010): a name, the value of the "acr" claim (OIDC Core §2),
 * on one of the predicates of the provider. Never stored on a session: a session satisfies every class
 * whose predicate holds on its proofs (architecture §3.2). The constants are the names of the default
 * policy, the Keycloak levels and the two names of OpenID Connect EAP ACR Values 1.0.
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

    public function isSatisfiedBy(AuthenticationProofs $proofs): bool
    {
        return $this->predicate->isSatisfiedBy($proofs);
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
}
