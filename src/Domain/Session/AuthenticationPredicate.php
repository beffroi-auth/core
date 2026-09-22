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
     * At least one proof, whatever the method.
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

    public function isSatisfiedBy(AuthenticationProofs $proofs): bool
    {
        return match ($this) {
            self::SingleFactor => true,
            self::TwoFactors => count($proofs->factors()) >= 2
                || array_any($proofs->methods(), static fn (AuthenticationMethod $method): bool => $method->isMultipleFactors()),
            self::PhishingResistant => $proofs->has(new AuthenticationMethod(AuthenticationMethod::HARDWARE_KEY))
                || $proofs->has(new AuthenticationMethod(AuthenticationMethod::SOFTWARE_KEY)),
            self::PhishingResistantHardware => $proofs->has(new AuthenticationMethod(AuthenticationMethod::HARDWARE_KEY)),
        };
    }
}
