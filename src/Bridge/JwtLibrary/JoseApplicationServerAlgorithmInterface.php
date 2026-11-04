<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Key\ApplicationServerAlgorithmInterface;

interface JoseApplicationServerAlgorithmInterface extends ApplicationServerAlgorithmInterface, JoseKeyAlgorithmInterface
{
}
