<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Session;

use function array_any;
use function count;

/**
 * What a class of a tenant's policy requires of the proofs of a session (decision 0010): the predicates
 * are the provider's and a tenant names them, it never defines one. A predicate holds on more proofs
 * whenever it held on fewer, which is what lets the provider say ahead which ones its logins can prove.
 */
enum AuthenticationPredicate: string
{
    /**
     * At least one method, whatever it is.
     */
    case SingleFactor = 'single_factor';

    /**
     * Two methods of different kinds (knowledge, possession, inherence, RFC 8176 §2), or a method that
     * counts as several factors by itself ("mfa").
     */
    case TwoFactors = 'two_factors';

    /**
     * A proof of possession of a key, "swk" or "hwk" (OpenID Connect EAP ACR Values 1.0, "phr").
     */
    case PhishingResistant = 'phishing_resistant';

    /**
     * A proof of possession of a key held in hardware, "hwk" (EAP ACR Values 1.0, "phrh").
     */
    case PhishingResistantHardware = 'phishing_resistant_hardware';

    /**
     * A predicate reads which methods were proven and nothing else: not when, not how often. It takes the
     * methods themselves, so that asking what a login *could* prove needs no proofs invented for the
     * occasion, and no date fabricated to carry them.
     *
     * @param list<AuthenticationMethod> $methods
     */
    public function isSatisfiedBy(array $methods): bool
    {
        return match ($this) {
            self::SingleFactor => $methods !== [],
            self::TwoFactors => count(AuthenticationMethod::factorsOf($methods)) >= 2
                || array_any($methods, static fn (AuthenticationMethod $method): bool => $method->isMultipleFactors()),
            self::PhishingResistant => self::holds($methods, AuthenticationMethod::HARDWARE_KEY) || self::holds($methods, AuthenticationMethod::SOFTWARE_KEY),
            self::PhishingResistantHardware => self::holds($methods, AuthenticationMethod::HARDWARE_KEY),
        };
    }

    /**
     * @param list<AuthenticationMethod> $methods
     */
    private static function holds(array $methods, string $value): bool
    {
        return array_any($methods, static fn (AuthenticationMethod $method): bool => $method->value === $value);
    }
}
