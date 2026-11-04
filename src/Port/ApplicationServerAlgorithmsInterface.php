<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\ApplicationServerAlgorithmInterface;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;

interface ApplicationServerAlgorithmsInterface extends KeyAlgorithmsInterface
{
    /**
     * @return list<ApplicationServerAlgorithmInterface>
     */
    public function all(): array;

    /**
     * @throws UnsupportedAlgorithmException
     */
    public function named(string $name): ApplicationServerAlgorithmInterface;
}
