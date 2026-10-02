<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary\Algorithm;

use Beffroi\Core\Bridge\JwtLibrary\JoseContentEncryptionAlgorithmInterface;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM as JoseA256Gcm;
use Jose\Component\Encryption\Algorithm\ContentEncryptionAlgorithm as JoseAlgorithm;

/**
 * AES GCM with a 256 bit key (RFC 7518 §5.3), the only content encryption this provider opens a payload
 * with: one pass, authenticated, and none of the composition the CBC-HMAC ones ask for.
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
