<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Exception\UnsupportedAlgorithmException;

interface JoseAlgorithmRegistryInterface
{
    /**
     * @return list<JoseKeyAlgorithmInterface> in the order they are wired
     */
    public function all(): array;

    public function find(string $name): ?JoseKeyAlgorithmInterface;

    public function refusalOf(string $name): UnsupportedAlgorithmException;
}
