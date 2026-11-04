<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Key\ContentEncryptionAlgorithmInterface;
use Jose\Component\Encryption\Algorithm\ContentEncryptionAlgorithm as JoseAlgorithm;

interface JoseContentEncryptionAlgorithmInterface extends ContentEncryptionAlgorithmInterface
{
    public function joseAlgorithm(): JoseAlgorithm;
}
