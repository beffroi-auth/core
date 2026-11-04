<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Bridge\Algorithm\WiredAlgorithms;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;
use Beffroi\Core\Port\ApplicationServerAlgorithmsInterface;

final readonly class JoseApplicationServerAlgorithms implements ApplicationServerAlgorithmsInterface, JoseAlgorithmRegistryInterface
{
    /**
     * @var WiredAlgorithms<JoseApplicationServerAlgorithmInterface>
     */
    private WiredAlgorithms $wired;

    /**
     * @param iterable<JoseApplicationServerAlgorithmInterface> $algorithms
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
