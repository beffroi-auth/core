<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\KeyEncryptionAlgorithmInterface;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;

interface KeyEncryptionAlgorithmsInterface extends KeyAlgorithmsInterface
{
    /**
     * @return list<KeyEncryptionAlgorithmInterface>
     */
    public function all(): array;

    /**
     * @throws UnsupportedAlgorithmException
     */
    public function named(string $name): KeyEncryptionAlgorithmInterface;
}
