<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

final readonly class SingleFactor extends AuthenticationPredicate
{
    public const string NAME = 'single_factor';

    public function name(): string
    {
        return self::NAME;
    }

    public function isSatisfiedBy(array $methods): bool
    {
        return $methods !== [];
    }

    public function holdsOnEveryLogin(): bool
    {
        return true;
    }

    protected function reads(AuthenticationMethod $method): bool
    {
        return true;
    }
}
