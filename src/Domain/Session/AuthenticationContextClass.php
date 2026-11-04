<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use Beffroi\Core\Exception\InvalidArgumentException;
use function in_array;
use function preg_match;
use function sprintf;
use Stringable;

final readonly class AuthenticationContextClass implements Stringable
{
    public const string SINGLE_FACTOR = '1';

    public const string TWO_FACTORS = '2';

    public const string PHISHING_RESISTANT = 'phr';

    public const string PHISHING_RESISTANT_HARDWARE = 'phrh';

    /**
     * OIDC Core §2
     */
    private const string PATTERN = '/^[\x21-\x7E]{1,255}$/D';

    public function __construct(
        public string $value,
        public AuthenticationPredicateInterface $predicate,
    ) {
        if (! self::isWellFormed($value)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a well-formed authentication context class reference.', $value));
        }
        self::assertRegisteredMeaning($value, $predicate);
    }

    public static function singleFactor(): self
    {
        return new self(self::SINGLE_FACTOR, new SingleFactor());
    }

    public static function twoFactors(): self
    {
        return new self(self::TWO_FACTORS, new TwoFactors());
    }

    public static function phishingResistant(): self
    {
        return new self(self::PHISHING_RESISTANT, new PhishingResistant());
    }

    public static function phishingResistantHardware(): self
    {
        return new self(self::PHISHING_RESISTANT_HARDWARE, new PhishingResistantHardware());
    }

    public static function isWellFormed(string $value): bool
    {
        return preg_match(self::PATTERN, $value) === 1;
    }

    /**
     * @param list<AuthenticationMethod> $methods
     */
    public function isSatisfiedBy(array $methods): bool
    {
        return $this->predicate->isSatisfiedBy($methods);
    }

    /**
     * By name only: a policy names each class once.
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
     * OIDC Core §2
     */
    private static function assertRegisteredMeaning(string $value, AuthenticationPredicateInterface $predicate): void
    {
        $allowed = match ($value) {
            self::PHISHING_RESISTANT => [PhishingResistant::NAME, PhishingResistantHardware::NAME],
            self::PHISHING_RESISTANT_HARDWARE => [PhishingResistantHardware::NAME],
            default => null,
        };
        if ($allowed !== null && ! in_array($predicate->name(), $allowed, true)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is a registered name (OpenID Connect EAP ACR Values 1.0) and keeps its meaning: it cannot be named on the "%s" predicate.',
                $value,
                $predicate->name(),
            ));
        }
    }
}
