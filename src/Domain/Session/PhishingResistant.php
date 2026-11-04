<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

/**
 * OpenID Connect EAP ACR Values 1.0, "phr"
 */
final readonly class PhishingResistant extends AuthenticationPredicate
{
    public const string NAME = 'phishing_resistant';

    public function name(): string
    {
        return self::NAME;
    }

    /**
     * One key proof is the whole of this predicate, so it holds exactly when it admits.
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
