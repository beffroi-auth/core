<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Key\KeyEncryptionAlgorithmInterface;
use Jose\Component\Encryption\Algorithm\KeyEncryptionAlgorithm as JoseAlgorithm;

interface JoseKeyEncryptionAlgorithmInterface extends JoseKeyAlgorithmInterface, KeyEncryptionAlgorithmInterface
{
    public function joseAlgorithm(): JoseAlgorithm;
}
