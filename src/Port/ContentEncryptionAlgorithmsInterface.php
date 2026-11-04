<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\ContentEncryptionAlgorithmInterface;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;

interface ContentEncryptionAlgorithmsInterface
{
    /**
     * @return list<ContentEncryptionAlgorithmInterface>
     */
    public function all(): array;

    /**
     * @throws UnsupportedAlgorithmException
     */
    public function named(string $name): ContentEncryptionAlgorithmInterface;
}
