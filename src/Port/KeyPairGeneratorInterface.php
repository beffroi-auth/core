<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Key\GeneratedKeyPair;
use Heidi\Core\Domain\Key\KeyAlgorithmInterface;

interface KeyPairGeneratorInterface
{
    public function generate(KeyAlgorithmInterface $algorithm): GeneratedKeyPair;
}
