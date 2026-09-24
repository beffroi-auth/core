<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\Algorithm;

use function array_values;
use Heidi\Core\Domain\Algorithm\NamedAlgorithmInterface;
use Heidi\Core\Exception\InvalidArgumentException;
use function sprintf;

/**
 * What the wiring gave a registry: the algorithms in the order they were wired, each name held once, and
 * the lookup of a name among them. Every registry of the provider is one of these plus the refusal it
 * words for its own use, so that the order of a wiring, its refusal to carry a name twice and the way a
 * name is resolved are written once for the three of them (issue #98).
 *
 * What a registry does not find is its own to refuse: the message names what the provider signs with, is
 * encrypted to with, encrypts a payload with or hashes a password with, and only the registry of that
 * use can say it.
 *
 * @template T of NamedAlgorithmInterface
 */
final readonly class WiredAlgorithms
{
    /**
     * @var list<T> in the order they are wired
     */
    public array $algorithms;

    /**
     * @param iterable<T> $algorithms in the order they are wired, none of them twice
     *
     * @throws InvalidArgumentException when a name is wired twice: which of the two would answer is not
     *                                  something a deployment should have to guess
     */
    public function __construct(iterable $algorithms)
    {
        $wired = [];
        foreach ($algorithms as $algorithm) {
            $name = $algorithm->name();
            if (isset($wired[$name])) {
                throw new InvalidArgumentException(sprintf('The algorithm "%s" is wired twice.', $name));
            }
            $wired[$name] = $algorithm;
        }
        $this->algorithms = array_values($wired);
    }

    /**
     * Null when nothing wired carries that name; the caller words the refusal.
     *
     * @return T|null
     */
    public function named(string $name): ?NamedAlgorithmInterface
    {
        foreach ($this->algorithms as $algorithm) {
            if ($algorithm->name() === $name) {
                return $algorithm;
            }
        }

        return null;
    }

    /**
     * The names, in the order they are wired: what a refusal lists.
     *
     * @return list<non-empty-string>
     */
    public function names(): array
    {
        $names = [];
        foreach ($this->algorithms as $algorithm) {
            $names[] = $algorithm->name();
        }

        return $names;
    }
}
