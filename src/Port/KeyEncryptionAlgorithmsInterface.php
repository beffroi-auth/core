<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Key\KeyEncryptionAlgorithmInterface;
use Heidi\Core\Exception\UnsupportedAlgorithmException;

/**
 * The JWE key management algorithms the wiring gives the provider, the one source of truth over them: the
 * encryption keys it generates, the JWE it opens and what the discovery document announces all read this
 * one. A deployment that is encrypted to by nobody wires none, and the document announces nothing rather
 * than an empty list.
 */
interface KeyEncryptionAlgorithmsInterface extends KeyAlgorithmsInterface
{
    /**
     * @return list<KeyEncryptionAlgorithmInterface> in the order they are wired
     */
    public function all(): array;

    /**
     * @throws UnsupportedAlgorithmException when nothing wired is encrypted to under that name
     */
    public function named(string $name): KeyEncryptionAlgorithmInterface;
}
