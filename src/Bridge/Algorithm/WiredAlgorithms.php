<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Algorithm;

use function array_values;
use Beffroi\Core\Domain\Algorithm\NamedAlgorithmInterface;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;
use function implode;
use function sprintf;

/**
 * What the wiring gave a registry: the algorithms in the order they were wired, each name held once, the
 * lookup of a name among them and what a refusal lists, a wiring that holds nothing included. Every
 * registry of the provider is one of these plus the words of its own refusal, so that the order of a
 * wiring, its refusal to carry a name twice, the way a name is resolved and the shape of a refusal are
 * written once for all of them (issues #98, #611).
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

    /**
     * The refusal of a name nothing wired carries, in the words of the registry this wiring belongs to:
     * the sentence it has for a wiring, which names what was refused and lists what is wired, or the
     * sentence it has for a wiring that holds nothing, where there is nothing to list. Which of the two is
     * said is answered here, so that a registry words both and reads neither.
     *
     * @param non-empty-string $listed  a format taking the name refused, then the names wired, comma separated
     * @param non-empty-string $nothing a format taking the name refused
     */
    public function refusalOf(string $name, string $listed, string $nothing): UnsupportedAlgorithmException
    {
        if ($this->algorithms === []) {
            return new UnsupportedAlgorithmException(sprintf($nothing, $name));
        }

        return new UnsupportedAlgorithmException(sprintf($listed, $name, implode(', ', $this->names())));
    }
}
