<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use function array_map;
use Beffroi\Core\Bridge\Algorithm\WiredAlgorithms;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;
use Beffroi\Core\Port\SignatureAlgorithmsInterface;
use function implode;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\Algorithm\SignatureAlgorithm as JoseAlgorithm;
use function sprintf;

final readonly class JoseSignatureAlgorithms implements JoseAlgorithmRegistryInterface, SignatureAlgorithmsInterface
{
    /**
     * @var WiredAlgorithms<JoseSignatureAlgorithmInterface>
     */
    private WiredAlgorithms $wired;

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

    public function find(string $name): ?JoseSignatureAlgorithmInterface
    {
        return $this->wired->named($name);
    }

    public function named(string $name): JoseSignatureAlgorithmInterface
    {
        return $this->find($name) ?? throw $this->refusalOf($name);
    }

    public function refusalOf(string $name): UnsupportedAlgorithmException
    {
        return new UnsupportedAlgorithmException(sprintf(
            'This provider does not sign with "%s"; it signs with %s.',
            $name,
            implode(', ', $this->wired->names()),
        ));
    }

    public function defaultAlgorithm(): JoseSignatureAlgorithmInterface
    {
        return $this->algorithms[0];
    }

    public function manager(): AlgorithmManager
    {
        return $this->manager;
    }
}
