<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Jose\Component\Core\AlgorithmManager;

/**
 * The algorithms a JWE is made and opened with, the two registries of one use read as one: web-token/
 * jwt-library takes the key management algorithms and the content encryption ones in a single manager,
 * and a builder and a reader are given the same one, so the provider cannot mint what it cannot open, nor
 * open what it would refuse to mint.
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
