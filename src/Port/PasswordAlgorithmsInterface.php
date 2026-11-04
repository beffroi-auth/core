<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\PasswordAlgorithmInterface;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;

interface PasswordAlgorithmsInterface
{
    /**
     * @return non-empty-list<PasswordAlgorithmInterface>
     */
    public function all(): array;

    /**
     * @throws UnsupportedAlgorithmException
     */
    public function named(string $name): PasswordAlgorithmInterface;

    /**
     * The first one wired.
     */
    public function defaultAlgorithm(): PasswordAlgorithmInterface;
}
