<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

/**
 * At least one method, whatever it is (decision 0010): the predicate every login of this provider
 * satisfies, and the one a policy is built from, every session having an "acr" (architecture §4.6). It is
 * also the floor of a tenant that names none (decision 0025), which admits every login this provider
 * serves.
 */
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
