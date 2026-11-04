<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Key\SignatureAlgorithmInterface;
use Jose\Component\Signature\Algorithm\SignatureAlgorithm as JoseAlgorithm;

interface JoseSignatureAlgorithmInterface extends JoseKeyAlgorithmInterface, SignatureAlgorithmInterface
{
    public function joseAlgorithm(): JoseAlgorithm;
}
