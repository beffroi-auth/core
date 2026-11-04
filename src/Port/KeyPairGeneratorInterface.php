<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\GeneratedKeyPair;
use Beffroi\Core\Domain\Key\KeyAlgorithmInterface;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;

interface KeyPairGeneratorInterface
{
    /**
     * @throws UnsupportedAlgorithmException
     */
    public function generate(KeyAlgorithmInterface $algorithm): GeneratedKeyPair;
}
