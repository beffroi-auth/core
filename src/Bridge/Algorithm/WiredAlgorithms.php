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
 * @template T of NamedAlgorithmInterface
 */
final readonly class WiredAlgorithms
{
    /**
     * @var list<T> in the order they are wired
     */
    public array $algorithms;

    /**
     * @param iterable<T> $algorithms
     *
     * @throws InvalidArgumentException when a name is wired twice
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
