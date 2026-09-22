<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Key\ContentEncryptionAlgorithmInterface;
use Heidi\Core\Exception\UnsupportedAlgorithmException;

/**
 * The JWE content encryption algorithms the wiring gives the provider: what it opens a payload with and
 * what it announces in "request_object_encryption_enc_values_supported". They take no key of the tenant,
 * so they have a registry of their own and not a use of KeyAlgorithmsInterface.
 */
interface ContentEncryptionAlgorithmsInterface
{
    /**
     * @return list<ContentEncryptionAlgorithmInterface> in the order they are wired
     */
    public function all(): array;

    /**
     * @throws UnsupportedAlgorithmException when nothing wired encrypts a payload under that name
     */
    public function named(string $name): ContentEncryptionAlgorithmInterface;
}
