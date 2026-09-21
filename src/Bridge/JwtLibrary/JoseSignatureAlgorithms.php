<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary;

use function array_values;
use Heidi\Core\Exception\InvalidArgumentException;
use Heidi\Core\Exception\UnsupportedAlgorithmException;
use Heidi\Core\Port\SignatureAlgorithmsInterface;
use function implode;
use Jose\Component\Core\AlgorithmManager;
use function sprintf;

/**
 * The algorithms the wiring gave the provider, and the algorithm manager web-token/jwt-library signs and
 * verifies with, built from the same list: the document cannot announce an algorithm the signer refuses,
 * nor the signer accept one the document leaves out. The first wired one is the default.
 */
final readonly class JoseSignatureAlgorithms implements SignatureAlgorithmsInterface
{
    /**
     * @var non-empty-list<JoseSignatureAlgorithmInterface>
     */
    private array $algorithms;

    private AlgorithmManager $manager;

    /**
     * @param iterable<JoseSignatureAlgorithmInterface> $algorithms at least one, the default first
     */
    public function __construct(iterable $algorithms)
    {
        $wired = [];
        $jose = [];
        foreach ($algorithms as $algorithm) {
            $name = $algorithm->name();
            if (isset($wired[$name])) {
                throw new InvalidArgumentException(sprintf('The algorithm "%s" is wired twice.', $name));
            }
            $wired[$name] = $algorithm;
            $jose[] = $algorithm->joseAlgorithm();
        }
        if ($wired === []) {
            throw new InvalidArgumentException('A provider signs with at least one algorithm.');
        }
        $this->algorithms = array_values($wired);
        $this->manager = new AlgorithmManager($jose);
    }

    /**
     * @return non-empty-list<JoseSignatureAlgorithmInterface>
     */
    public function all(): array
    {
        return $this->algorithms;
    }

    public function named(string $name): JoseSignatureAlgorithmInterface
    {
        foreach ($this->algorithms as $algorithm) {
            if ($algorithm->name() === $name) {
                return $algorithm;
            }
        }

        throw new UnsupportedAlgorithmException(sprintf('This provider does not sign with "%s"; it signs with %s.', $name, implode(', ', $this->names())));
    }

    public function defaultAlgorithm(): JoseSignatureAlgorithmInterface
    {
        return $this->algorithms[0];
    }

    /**
     * What a builder and a verifier of the library are given.
     */
    public function manager(): AlgorithmManager
    {
        return $this->manager;
    }

    /**
     * @return non-empty-list<non-empty-string>
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
