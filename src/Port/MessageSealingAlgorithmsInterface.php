<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\MessageSealingAlgorithmInterface;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;

interface MessageSealingAlgorithmsInterface extends KeyAlgorithmsInterface
{
    /**
     * @return list<MessageSealingAlgorithmInterface>
     */
    public function all(): array;

    /**
     * The first one wired.
     *
     * @throws UnsupportedAlgorithmException when none is wired
     */
    public function defaultAlgorithm(): MessageSealingAlgorithmInterface;

    /**
     * @throws UnsupportedAlgorithmException
     */
    public function named(string $name): MessageSealingAlgorithmInterface;
}
