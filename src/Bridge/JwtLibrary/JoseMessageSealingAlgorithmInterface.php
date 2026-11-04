<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Key\MessageSealingAlgorithmInterface;

interface JoseMessageSealingAlgorithmInterface extends JoseKeyAlgorithmInterface, MessageSealingAlgorithmInterface
{
    public function keyEncryption(): JoseKeyEncryptionAlgorithmInterface;

    public function contentEncryption(): JoseContentEncryptionAlgorithmInterface;
}
