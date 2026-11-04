<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

use Beffroi\Core\Domain\Algorithm\NamedAlgorithmInterface;

/**
 * A predicate that holds on some proofs still holds when a proof is added.
 */
interface AuthenticationPredicateInterface extends NamedAlgorithmInterface
{
    /**
     * @param list<AuthenticationMethod> $methods
     */
    public function isSatisfiedBy(array $methods): bool;

    /**
     * Whether a credential proving these methods can be needed in a login that meets this floor, not whether it
     * meets it alone.
     *
     * @param list<AuthenticationMethod> $methods
     */
    public function admits(array $methods): bool;

    public function holdsOnEveryLogin(): bool;
}
