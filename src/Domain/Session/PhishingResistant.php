<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

/**
 * A proof of possession of a key, "swk" or "hwk" (OpenID Connect EAP ACR Values 1.0, "phr"): a passkey,
 * synced or bound to its authenticator.
 */
final readonly class PhishingResistant extends AuthenticationPredicate
{
    public const string NAME = 'phishing_resistant';

    public function name(): string
    {
        return self::NAME;
    }

    /**
     * One key proof is the whole of this predicate, so a login satisfies it exactly when one of its
     * proofs is a method it reads, which is the question admits() asks of a credential.
     */
    public function isSatisfiedBy(array $methods): bool
    {
        return $this->admits($methods);
    }

    protected function reads(AuthenticationMethod $method): bool
    {
        return $method->value === AuthenticationMethod::HARDWARE_KEY || $method->value === AuthenticationMethod::SOFTWARE_KEY;
    }
}
