<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Exception\InvalidArgumentException;

/**
 * Where the FIDO metadata the provider trusts is kept (architecture §3.4): the payload of a Metadata BLOB
 * (FIDO Metadata Service v3.1.1 §3.1.7, its payload §3.1.6: a JSON document of entries, each naming its
 * own authenticator by AAID, AAGUID or attestation certificate key identifiers and carrying its status
 * reports) once the console verified its signature under the root it pins, or one the console wrapped
 * around a statement a manufacturer published. What is in the store was verified when it was written and
 * is trusted like the sealed keys next to it are; the ceremonies read it, they never write it.
 */
interface MetadataBlobStoreInterface
{
    /**
     * Writes a verified payload under a name, replacing the one of that name.
     *
     * @param non-empty-string $name letters, digits, dots, dashes and underscores; a name is what the
     *                               console derives from the source, so that the same source replaces
     *                               itself
     *
     * @throws InvalidArgumentException on a name outside of that alphabet
     */
    public function write(string $name, string $payload): void;

    /**
     * Every payload, by name, in the order of the names.
     *
     * @return array<string, string>
     */
    public function readAll(): array;
}
