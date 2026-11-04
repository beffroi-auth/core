<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\SignatureAlgorithmInterface;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;

interface SignatureAlgorithmsInterface extends KeyAlgorithmsInterface
{
    /**
     * @return non-empty-list<SignatureAlgorithmInterface>
     */
    public function all(): array;

    /**
     * @throws UnsupportedAlgorithmException
     */
    public function named(string $name): SignatureAlgorithmInterface;

    /**
     * The first one wired.
     */
    public function defaultAlgorithm(): SignatureAlgorithmInterface;
}
