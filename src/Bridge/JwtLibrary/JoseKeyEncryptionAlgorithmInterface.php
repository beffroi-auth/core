<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary;

use Heidi\Core\Domain\Key\KeyEncryptionAlgorithmInterface;
use Jose\Component\Encryption\Algorithm\KeyEncryptionAlgorithm as JoseAlgorithm;

/**
 * One JWE key management algorithm as web-token/jwt-library knows it: what unwraps, or agrees on, the
 * content encryption key, and the key pair a client is given to encrypt to.
 */
interface JoseKeyEncryptionAlgorithmInterface extends JoseKeyAlgorithmInterface, KeyEncryptionAlgorithmInterface
{
    /**
     * What the algorithm manager of the library opens a JWE with.
     */
    public function joseAlgorithm(): JoseAlgorithm;
}
