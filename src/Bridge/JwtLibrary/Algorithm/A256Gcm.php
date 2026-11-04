<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary\Algorithm;

use Beffroi\Core\Bridge\JwtLibrary\JoseContentEncryptionAlgorithmInterface;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM as JoseA256Gcm;
use Jose\Component\Encryption\Algorithm\ContentEncryptionAlgorithm as JoseAlgorithm;

/**
 * RFC 7518 §5.3
 */
final readonly class A256Gcm implements JoseContentEncryptionAlgorithmInterface
{
    public function name(): string
    {
        return 'A256GCM';
    }

    public function joseAlgorithm(): JoseAlgorithm
    {
        return new JoseA256Gcm();
    }
}
