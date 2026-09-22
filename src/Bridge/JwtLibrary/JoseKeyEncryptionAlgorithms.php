<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary;

use function array_values;
use Heidi\Core\Exception\InvalidArgumentException;
use Heidi\Core\Exception\UnsupportedAlgorithmException;
use Heidi\Core\Port\KeyEncryptionAlgorithmsInterface;
use function implode;
use function sprintf;

/**
 * The JWE key management algorithms the wiring gave the provider: the encryption keys it generates, the
 * JWE it opens and what the discovery document announces all read this one, so the document cannot
 * announce an algorithm the decrypter refuses. A deployment nobody encrypts to wires none, and this list
 * is empty.
 */
final readonly class JoseKeyEncryptionAlgorithms implements KeyEncryptionAlgorithmsInterface
{
    /**
     * @var list<JoseKeyEncryptionAlgorithmInterface>
     */
    private array $algorithms;

    /**
     * @param iterable<JoseKeyEncryptionAlgorithmInterface> $algorithms in the order they are wired, none of them twice
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
     * @return list<JoseKeyEncryptionAlgorithmInterface>
     */
    public function all(): array
    {
        return $this->algorithms;
    }

    public function named(string $name): JoseKeyEncryptionAlgorithmInterface
    {
        foreach ($this->algorithms as $algorithm) {
            if ($algorithm->name() === $name) {
                return $algorithm;
            }
        }
        if ($this->algorithms === []) {
            throw new UnsupportedAlgorithmException(sprintf('This provider is encrypted to with nothing; "%s" is not read.', $name));
        }

        throw new UnsupportedAlgorithmException(sprintf('This provider is not encrypted to with "%s"; it is encrypted to with %s.', $name, implode(', ', $this->names())));
    }

    /**
     * @return list<non-empty-string>
     */
    private function names(): array
    {
        $names = [];
        foreach ($this->algorithms as $algorithm) {
            $names[] = $algorithm->name();
        }

        return $names;
    }
}
