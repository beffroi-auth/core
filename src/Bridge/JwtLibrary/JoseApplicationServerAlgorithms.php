<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Bridge\Algorithm\WiredAlgorithms;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;
use Beffroi\Core\Port\ApplicationServerAlgorithmsInterface;

/**
 * The algorithms of the keys the tenant hands out itself, as the wiring gave them. A deployment that
 * reaches nobody by push wires none, and then there is no such key to draw and no page that hands one
 * out: unlike the signature registry, an empty one is a deployment and not a mistake.
 */
final readonly class JoseApplicationServerAlgorithms implements ApplicationServerAlgorithmsInterface, JoseAlgorithmRegistryInterface
{
    /**
     * @var WiredAlgorithms<JoseApplicationServerAlgorithmInterface>
     */
    private WiredAlgorithms $wired;

    /**
     * @param iterable<JoseApplicationServerAlgorithmInterface> $algorithms in the order they are wired
     */
    public function __construct(iterable $algorithms)
    {
        $this->wired = new WiredAlgorithms($algorithms);
    }

    /**
     * @return list<JoseApplicationServerAlgorithmInterface>
     */
    public function all(): array
    {
        return $this->wired->algorithms;
    }

    public function find(string $name): ?JoseApplicationServerAlgorithmInterface
    {
        return $this->wired->named($name);
    }

    public function named(string $name): JoseApplicationServerAlgorithmInterface
    {
        return $this->find($name) ?? throw $this->refusalOf($name);
    }

    public function refusalOf(string $name): UnsupportedAlgorithmException
    {
        return $this->wired->refusalOf(
            $name,
            'This provider hands out no key named "%s"; it hands out %s.',
            'This provider hands out no key named "%s"; it hands out none.',
        );
    }
}
