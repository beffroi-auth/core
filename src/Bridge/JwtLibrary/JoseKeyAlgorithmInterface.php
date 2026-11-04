<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Key\KeyAlgorithmInterface;
use Jose\Component\Core\JWK;

interface JoseKeyAlgorithmInterface extends KeyAlgorithmInterface
{
    /**
     * A fresh private key carrying its "alg" and "use"; the generator computes its "kid".
     */
    public function generateKey(): JWK;
}
