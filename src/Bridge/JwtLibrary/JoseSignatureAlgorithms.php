<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary;

use function array_map;
use Heidi\Core\Exception\InvalidArgumentException;
use Heidi\Core\Exception\UnsupportedAlgorithmException;
use Heidi\Core\Port\SignatureAlgorithmsInterface;
use function implode;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\Algorithm\SignatureAlgorithm as JoseAlgorithm;
use function sprintf;

/**
 * The algorithms the wiring gave the provider, and the algorithm manager web-token/jwt-library signs and
 * verifies with, built from the same list: the document cannot announce an algorithm the signer refuses,
 * nor the signer accept one the document leaves out. The first wired one is the default.
 */
final readonly class JoseSignatureAlgorithms implements SignatureAlgorithmsInterface
{
    /**
     * @var WiredAlgorithms<JoseSignatureAlgorithmInterface> the wiring, held and looked up as in every registry
     */
    private WiredAlgorithms $wired;

    /**
     * @var non-empty-list<JoseSignatureAlgorithmInterface> the same list, narrowed by the check the
     * constructor makes: a provider signs with at least one algorithm, which is a promise of this
     * registry and of no other, and what "defaultAlgorithm()" rests on
     */
    private array $algorithms;

    private AlgorithmManager $manager;

    /**
     * @param iterable<JoseSignatureAlgorithmInterface> $algorithms at least one, the default first
     */
    public function __construct(iterable $algorithms)
    {
        $wired = new WiredAlgorithms($algorithms);
        if ($wired->algorithms === []) {
            throw new InvalidArgumentException('A provider signs with at least one algorithm.');
        }
        $this->wired = $wired;
        $this->algorithms = $wired->algorithms;
        $this->manager = new AlgorithmManager(array_map(
            static fn (JoseSignatureAlgorithmInterface $algorithm): JoseAlgorithm => $algorithm->joseAlgorithm(),
            $this->algorithms,
        ));
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
        return (
            $this->wired->named($name) ?? throw new UnsupportedAlgorithmException(sprintf(
                'This provider does not sign with "%s"; it signs with %s.',
                $name,
                implode(', ', $this->wired->names()),
            ))
        );
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
}
