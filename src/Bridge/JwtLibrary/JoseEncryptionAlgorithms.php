<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Jose\Component\Core\AlgorithmManager;

/**
 * The key management and content encryption algorithms read as one: web-token/jwt-library takes both in a
 * single manager.
 */
final readonly class JoseEncryptionAlgorithms
{
    public function __construct(
        public JoseKeyEncryptionAlgorithms $keyEncryption,
        public JoseContentEncryptionAlgorithms $contentEncryption,
    ) {
    }

    public function manager(): AlgorithmManager
    {
        $jose = [];
        foreach ($this->keyEncryption->all() as $algorithm) {
            $jose[] = $algorithm->joseAlgorithm();
        }
        foreach ($this->contentEncryption->all() as $algorithm) {
            $jose[] = $algorithm->joseAlgorithm();
        }

        return new AlgorithmManager($jose);
    }
}
