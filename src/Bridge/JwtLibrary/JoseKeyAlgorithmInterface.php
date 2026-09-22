<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary;

use Heidi\Core\Domain\Key\KeyAlgorithmInterface;
use Jose\Component\Core\JWK;

/**
 * One algorithm a key pair is generated for, as web-token/jwt-library knows it. A new algorithm is one
 * class implementing the interface of its use and one line in the wiring; nothing anywhere matches on
 * which algorithm it is.
 */
interface JoseKeyAlgorithmInterface extends KeyAlgorithmInterface
{
    /**
     * A fresh private key for this algorithm, carrying its "alg" and "use"; the "kid" is the RFC 7638
     * thumbprint the generator computes.
     */
    public function generateKey(): JWK;
}
