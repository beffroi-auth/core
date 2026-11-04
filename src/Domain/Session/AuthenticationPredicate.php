<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use function array_any;

abstract readonly class AuthenticationPredicate implements AuthenticationPredicateInterface
{
    final public function admits(array $methods): bool
    {
        return array_any($methods, fn (AuthenticationMethod $method): bool => $this->reads($method));
    }

    public function holdsOnEveryLogin(): bool
    {
        return false;
    }

    /**
     * Whether this predicate looks at that method at all.
     */
    abstract protected function reads(AuthenticationMethod $method): bool;
}
