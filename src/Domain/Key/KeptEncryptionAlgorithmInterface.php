<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

/**
 * Not a KeyEncryptionAlgorithmInterface: kept out of the JWK Set, and what opens a JWE never answers it.
 */
interface KeptEncryptionAlgorithmInterface extends KeyAlgorithmInterface
{
}
