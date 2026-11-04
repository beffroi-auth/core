<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Session;

use Beffroi\Core\Bridge\Algorithm\WiredAlgorithms;
use Beffroi\Core\Domain\Session\AuthenticationPredicateInterface;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\AuthenticationPredicatesInterface;
use function implode;
use function sprintf;

final readonly class WiredAuthenticationPredicates implements AuthenticationPredicatesInterface
{
    /**
     * @var WiredAlgorithms<AuthenticationPredicateInterface>
     */
    private WiredAlgorithms $wired;

    /**
     * @param iterable<AuthenticationPredicateInterface> $predicates at least one
     *
     * @throws InvalidArgumentException when a name is wired twice, or when nothing is wired at all
     */
    public function __construct(iterable $predicates)
    {
        $wired = new WiredAlgorithms($predicates);
        if ($wired->algorithms === []) {
            throw new InvalidArgumentException('A provider defines at least one authentication predicate: every session has an "acr".');
        }
        $this->wired = $wired;
    }

    public function named(string $name): AuthenticationPredicateInterface
    {
        return (
            $this->wired->named($name) ?? throw new InvalidArgumentException(sprintf(
                'This provider defines no authentication predicate "%s"; it defines %s.',
                $name,
                implode(', ', $this->wired->names()),
            ))
        );
    }
}
