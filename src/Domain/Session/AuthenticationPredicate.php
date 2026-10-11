<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use function array_any;

/**
 * What every predicate of the provider is written the same way, which is why that is here and once: a
 * predicate says which methods it reads, one by one, and what it admits is read off that one answer, so
 * that the two cannot drift apart. A predicate that reads nothing of a credential closes a way in, and a
 * predicate a single proof is the whole of holds exactly when it admits (PhishingResistant).
 *
 * The four predicates of decision 0010 extend this; a predicate this provider gains is a class beside
 * them and a line in the wiring, and a predicate written otherwise does not extend it and implements the
 * interface itself.
 */
abstract readonly class AuthenticationPredicate implements AuthenticationPredicateInterface
{
    final public function admits(array $methods): bool
    {
        return array_any($methods, fn (AuthenticationMethod $method): bool => $this->reads($method));
    }

    /**
     * False unless a predicate says otherwise: what holds on every login there is, the bottom a
     * vocabulary is built from, is said by the one predicate that is one and never assumed of a new one.
     */
    public function holdsOnEveryLogin(): bool
    {
        return false;
    }

    /**
     * Whether this predicate looks at that method at all. Each predicate says it of one method, and the
     * provider reads a set of them through admits(): a predicate that answered a set directly would say
     * twice which methods it reads.
     */
    abstract protected function reads(AuthenticationMethod $method): bool;
}
