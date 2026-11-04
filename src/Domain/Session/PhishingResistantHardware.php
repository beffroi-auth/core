<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

/**
 * OpenID Connect EAP ACR Values 1.0, "phrh"
 */
final readonly class PhishingResistantHardware extends AuthenticationPredicate
{
    public const string NAME = 'phishing_resistant_hardware';

    public function name(): string
    {
        return self::NAME;
    }

    /**
     * One hardware key proof is the whole of this predicate, so it holds exactly when it admits.
     */
    public function isSatisfiedBy(array $methods): bool
    {
        return $this->admits($methods);
    }

    protected function reads(AuthenticationMethod $method): bool
    {
        return $method->value === AuthenticationMethod::HARDWARE_KEY;
    }
}
