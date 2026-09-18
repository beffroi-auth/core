<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Session;

use function array_any;
use function count;
use Heidi\Core\Exception\InvalidArgumentException;
use function preg_match;
use function sprintf;
use Stringable;

/**
 * The "acr" claim (OIDC Core §2), computed from the proofs of a session, never stored (architecture §3.2).
 * Levels as numeric strings ("1", "2", the Keycloak vocabulary) and the EAP ACR Values 1.0 names for
 * passkeys. Each class is a predicate on the proofs; a session satisfies every class whose predicate holds.
 */
final readonly class AuthenticationContextClass implements Stringable
{
    public const string SINGLE_FACTOR = '1';

    /**
     * Two factors of different kinds, or an upstream "mfa" (RFC 8176 §2).
     */
    public const string TWO_FACTORS = '2';

    /**
     * EAP ACR Values 1.0: a passkey, in software or in hardware.
     */
    public const string PHISHING_RESISTANT = 'phr';

    /**
     * EAP ACR Values 1.0: a passkey in a hardware-protected module.
     */
    public const string PHISHING_RESISTANT_HARDWARE = 'phrh';

    /**
     * OIDC Core §2: printable ASCII without space.
     */
    private const string PATTERN = '/^[\x21-\x7E]{1,255}$/D';

    /**
     * Strongest first: the order a session is answered in when no class is requested.
     */
    private const array ABOVE_SINGLE_FACTOR = [self::PHISHING_RESISTANT_HARDWARE, self::PHISHING_RESISTANT, self::TWO_FACTORS];

    public function __construct(
        public string $value,
    ) {
        if (! self::isWellFormed($value)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a well-formed authentication context class reference.', $value));
        }
    }

    public static function singleFactor(): self
    {
        return new self(self::SINGLE_FACTOR);
    }

    public static function twoFactors(): self
    {
        return new self(self::TWO_FACTORS);
    }

    public static function phishingResistant(): self
    {
        return new self(self::PHISHING_RESISTANT);
    }

    public static function phishingResistantHardware(): self
    {
        return new self(self::PHISHING_RESISTANT_HARDWARE);
    }

    public static function isWellFormed(string $value): bool
    {
        return preg_match(self::PATTERN, $value) === 1;
    }

    /**
     * The strongest class the proofs satisfy; one proof of any method is at least the single factor.
     */
    public static function of(AuthenticationProofs $proofs): self
    {
        foreach (self::ABOVE_SINGLE_FACTOR as $value) {
            $class = new self($value);
            if ($class->isSatisfiedBy($proofs)) {
                return $class;
            }
        }

        return self::singleFactor();
    }

    /**
     * What "acr_values_supported" lists (OIDC Discovery §3): the classes a credential of the provider can prove.
     *
     * @return non-empty-list<string>
     */
    public static function supported(): array
    {
        return [self::SINGLE_FACTOR];
    }

    /**
     * A class the provider does not define is never satisfied (OIDC Core §2).
     */
    public function isSatisfiedBy(AuthenticationProofs $proofs): bool
    {
        return match ($this->value) {
            self::SINGLE_FACTOR => true,
            self::TWO_FACTORS => count($proofs->factors()) >= 2 || array_any($proofs->methods(), static fn (AuthenticationMethod $method): bool => $method->isMultipleFactors()),
            self::PHISHING_RESISTANT => $proofs->has(new AuthenticationMethod(AuthenticationMethod::HARDWARE_KEY)) || $proofs->has(new AuthenticationMethod(AuthenticationMethod::SOFTWARE_KEY)),
            self::PHISHING_RESISTANT_HARDWARE => $proofs->has(new AuthenticationMethod(AuthenticationMethod::HARDWARE_KEY)),
            default => false,
        };
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
