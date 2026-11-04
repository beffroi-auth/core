<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Session\AuthenticationPredicateInterface;
use Beffroi\Core\Exception\InvalidArgumentException;

interface AuthenticationPredicatesInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function named(string $name): AuthenticationPredicateInterface;
}
