<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use function array_any;
use function count;

/**
 * What a class of a tenant's policy requires of the proofs of a session (decision 0010): the predicates
 * are the provider's and a tenant names them, it never defines one. A predicate holds on more proofs
 * whenever it held on fewer, which is what lets the provider say ahead which ones its logins can prove.
 *
 * **A tenant also names one of them as the floor of every login of its users** (issue #327,
 * Tenant::$loginFloor): what a session must prove here at the least. That is why the floor is a predicate
 * and not a list of the kinds a tenant permits: a predicate only ever holds on more proofs, so a floor
 * can be raised and can never be turned into "the password and nothing else", which is the switch to do
 * it wrong that decision 0006 refuses. What the floor then permits is read off it, never kept beside it.
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
     * Whether a credential able to prove these methods has a place under this floor (issue #327): it has
     * one when the floor reads at least one of them, that is when the credential can be part of a login
     * this floor is met by and be needed in it. What the login page of a tenant offers, and what may be
     * enrolled there; a credential whose methods the floor never reads is a way in the tenant has closed.
     *
     * It is not "would this credential alone meet the floor": under the two factors a password meets
     * nothing by itself and is still half of what meets it. It is not "could a login holding it meet the
     * floor" either, which every credential satisfies as soon as a passkey is held beside it.
     *
     * @param list<AuthenticationMethod> $methods what one authentication with that credential may prove
     *                                            (ProvingCredentialInterface::provableMethods())
     */
    public function admits(array $methods): bool
    {
        return array_any($methods, fn (AuthenticationMethod $method): bool => $this->reads($method));
    }

    /**
     * Whether this predicate looks at that method at all: each of them above says which ones it reads,
     * and this says the same thing of one method, so that the two cannot drift apart.
     */
    private function reads(AuthenticationMethod $method): bool
    {
        return match ($this) {
            self::SingleFactor => true,
            self::TwoFactors => $method->factor() !== null || $method->isMultipleFactors(),
            self::PhishingResistant => $method->value === AuthenticationMethod::HARDWARE_KEY || $method->value === AuthenticationMethod::SOFTWARE_KEY,
            self::PhishingResistantHardware => $method->value === AuthenticationMethod::HARDWARE_KEY,
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
