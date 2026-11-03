<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Exception\RuntimeException;

/**
 * Where the list of models of authenticator the community keeps is kept (architecture §3.4): the document the
 * console downloaded and read as such a list, unsigned, which names models and nothing else. The page of the
 * passkeys and the console read it; no ceremony does, and nothing that decides a login.
 */
interface AuthenticatorListStoreInterface
{
    /**
     * Writes the document, replacing the one stored.
     *
     * @throws RuntimeException when the document cannot be written
     */
    public function write(string $document): void;

    /**
     * The stored document, or nothing when the console never stored one.
     *
     * @throws RuntimeException when a stored document cannot be read
     */
    public function read(): ?string;
}
