<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary;

use Heidi\Core\Domain\Key\SignatureAlgorithmInterface;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\SignatureAlgorithm as JoseAlgorithm;

/**
 * One algorithm as web-token/jwt-library knows it: what it signs and verifies with, and the key pair it
 * generates. A new algorithm is one class implementing this interface and one line in the wiring; nothing
 * anywhere matches on which algorithm it is.
 */
interface JoseSignatureAlgorithmInterface extends SignatureAlgorithmInterface
{
    /**
     * What the algorithm manager of the library signs and verifies with.
     */
    public function joseAlgorithm(): JoseAlgorithm;

    /**
     * A fresh private key for this algorithm, carrying its "alg" and "use"; the "kid" is the RFC 7638
     * thumbprint the generator computes.
     */
    public function generateKey(): JWK;
}
