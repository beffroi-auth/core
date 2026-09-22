<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary;

use Heidi\Core\Domain\Key\ContentEncryptionAlgorithmInterface;
use Jose\Component\Encryption\Algorithm\ContentEncryptionAlgorithm as JoseAlgorithm;

/**
 * One JWE content encryption algorithm as web-token/jwt-library knows it.
 */
interface JoseContentEncryptionAlgorithmInterface extends ContentEncryptionAlgorithmInterface
{
    /**
     * What the algorithm manager of the library decrypts the payload with.
     */
    public function joseAlgorithm(): JoseAlgorithm;
}
