<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Exception\RuntimeException;

/**
 * Unsigned: no ceremony reads it, nor anything that decides a login.
 */
interface AuthenticatorListStoreInterface
{
    /**
     * @throws RuntimeException
     */
    public function write(string $document): void;

    /**
     * @throws RuntimeException
     */
    public function read(): ?string;
}
