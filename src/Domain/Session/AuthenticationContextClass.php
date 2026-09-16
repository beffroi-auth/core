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
 * An Authentication Context Class Reference, the value of the "acr" claim (OIDC Core §2): what the
 * authentication that established a session satisfied. It is computed from the proofs of the session, never
 * stored nor configured, so that a proof added later moves it the way it moves auth_time (architecture
 * §3.2).
 *
 * The vocabulary is a level of authentication as a numeric string, the one Keycloak emits by default ("1"
 * after a password, "0" once the level expired): a relying party written for it reads the same value after
 * an import (decision 0002). The registered names of RFC 6711 each carry an enrolment meaning the provider
 * does not assert, and OIDC Core §2 forbids using a registered name in another sense; "0" says there is no
 * confidence the same person is there, which is not what a verified password establishes. Above the levels,
 * the two names of OpenID Connect EAP ACR Values 1.0 say what a passkey proves: phishing resistance, in a
 * hardware-protected module or not.
 *
 * Each class is a predicate on the proofs, and a session satisfies every class whose predicate holds: a
 * password and a hardware key satisfy "1", "2", "phr" and "phrh" at once. What a request names is matched
 * against that, exactly (OIDC Core §5.5.1.1); what nothing names is answered with the strongest class
 * satisfied, in the order below.
 */
final readonly class AuthenticationContextClass implements Stringable
{
    /**
     * One factor was proven: the password.
     */
    public const string SINGLE_FACTOR = '1';

    /**
     * Two factors of different kinds were proven, a password and a one-time password, or an upstream
     * provider asserted "mfa" (RFC 8176 §2).
     */
    public const string TWO_FACTORS = '2';

    /**
     * EAP ACR Values 1.0 "phr": proof of possession of a key, which a party controlling where the browser
     * goes cannot capture; a passkey, in software or in hardware.
     */
    public const string PHISHING_RESISTANT = 'phr';

    /**
     * EAP ACR Values 1.0 "phrh": the same, the private key held in a hardware-protected module.
     */
    public const string PHISHING_RESISTANT_HARDWARE = 'phrh';

    /**
     * OIDC Core §2: an absolute URI or a registered name, either way printable ASCII without space.
     */
    private const string PATTERN = '/^[\x21-\x7E]{1,255}$/D';

    /**
     * The classes above the single factor, strongest first: the order in which a session is answered
     * when no class is requested (architecture §3.2).
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
     * The strongest class the proofs of a session satisfy. A session holds at least one proof, and one
     * proof of any method is one factor, so the single factor is the floor.
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
     * What the provider can assert, what "acr_values_supported" lists (OIDC Discovery §3): the classes a
     * credential of the provider can prove. The password is the only credential today, so the single
     * factor is the only class; a second factor adds "2", a passkey "phr" and "phrh". A request naming
     * a class outside this list can never be met, whatever the session (see AuthorizationHandler).
     *
     * @return non-empty-list<string>
     */
    public static function supported(): array
    {
        return [self::SINGLE_FACTOR];
    }

    /**
     * Whether the proofs meet the predicate of this class. A class the provider does not define, a URI of
     * a federation or a name of RFC 6711, is never satisfied: the provider asserts nothing it has not
     * defined (OIDC Core §2).
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
