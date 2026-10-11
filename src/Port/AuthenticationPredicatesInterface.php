<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Session\AuthenticationPredicateInterface;
use Beffroi\Core\Exception\InvalidArgumentException;

/**
 * The authentication predicates the wiring gives the provider (decision 0010), the one source of truth
 * over them: what the "acr" vocabulary a tenant configures is read on, what a stored policy and a stored
 * floor are read back as, all one list. A name nothing wired carries is a policy this deployment cannot
 * account for, and it is refused rather than guessed.
 */
interface AuthenticationPredicatesInterface
{
    /**
     * @throws InvalidArgumentException when nothing wired carries that name, naming what is wired
     */
    public function named(string $name): AuthenticationPredicateInterface;
}
