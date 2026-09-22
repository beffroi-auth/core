<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary;

use Heidi\Core\Domain\Key\SignatureAlgorithmInterface;
use Jose\Component\Signature\Algorithm\SignatureAlgorithm as JoseAlgorithm;

/**
 * One JWS algorithm as web-token/jwt-library knows it: what it signs and verifies with, and the key pair
 * it generates.
 */
interface JoseSignatureAlgorithmInterface extends JoseKeyAlgorithmInterface, SignatureAlgorithmInterface
{
    /**
     * What the algorithm manager of the library signs and verifies with.
     */
    public function joseAlgorithm(): JoseAlgorithm;
}
