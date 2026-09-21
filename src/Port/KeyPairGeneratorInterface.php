<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Key\GeneratedKeyPair;
use Heidi\Core\Domain\Key\SignatureAlgorithmInterface;

interface KeyPairGeneratorInterface
{
    public function generate(SignatureAlgorithmInterface $algorithm): GeneratedKeyPair;
}
