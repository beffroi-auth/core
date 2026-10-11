<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Session;

use Beffroi\Core\Bridge\Algorithm\WiredAlgorithms;
use Beffroi\Core\Domain\Session\AuthenticationPredicateInterface;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\AuthenticationPredicatesInterface;
use function implode;
use function sprintf;

/**
 * The authentication predicates the wiring gave the provider (decision 0010), held and looked up as every
 * other wiring of this provider is (WiredAlgorithms): the order they are wired in, each name held once,
 * and the lookup of a name among them. A provider wired with none is refused here, where the wiring is
 * collected: every session has an "acr" (architecture §4.6), so a provider no policy can be written for
 * is not one it can be.
 */
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
