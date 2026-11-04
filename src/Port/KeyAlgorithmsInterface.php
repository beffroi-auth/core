<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\KeyAlgorithmInterface;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;

interface KeyAlgorithmsInterface
{
    /**
     * @return list<KeyAlgorithmInterface>
     */
    public function all(): array;

    /**
     * @throws UnsupportedAlgorithmException
     */
    public function named(string $name): KeyAlgorithmInterface;
}
