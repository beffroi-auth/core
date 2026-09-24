<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Key\GeneratedKeyPair;
use Heidi\Core\Domain\Key\KeyAlgorithmInterface;
use Heidi\Core\Exception\UnsupportedAlgorithmException;

interface KeyPairGeneratorInterface
{
    /**
     * @throws UnsupportedAlgorithmException when the algorithm is not one this provider is wired with:
     *                                       a key is generated for what the deployment holds, never for
     *                                       a name it was handed
     */
    public function generate(KeyAlgorithmInterface $algorithm): GeneratedKeyPair;
}
