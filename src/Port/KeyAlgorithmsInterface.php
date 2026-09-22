<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Key\KeyAlgorithmInterface;
use Heidi\Core\Exception\UnsupportedAlgorithmException;

/**
 * Every algorithm a key pair of the tenant may be generated for, whatever its use: what the key commands
 * take on their "--algorithm" option and what reads a stored key back. The registries of one use
 * (SignatureAlgorithmsInterface, KeyEncryptionAlgorithmsInterface) are this one narrowed, and this one is
 * built from them, so that a wiring is read in one place and the two cannot disagree (issue #98).
 */
interface KeyAlgorithmsInterface
{
    /**
     * @return list<KeyAlgorithmInterface> in the order they are wired, the signature ones first
     */
    public function all(): array;

    /**
     * @throws UnsupportedAlgorithmException when nothing wired carries that name
     */
    public function named(string $name): KeyAlgorithmInterface;
}
