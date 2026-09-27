<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary;

use Heidi\Core\Domain\Key\ApplicationServerAlgorithmInterface;

/**
 * One algorithm of a key the tenant hands out itself, as web-token/jwt-library generates it.
 */
interface JoseApplicationServerAlgorithmInterface extends ApplicationServerAlgorithmInterface, JoseKeyAlgorithmInterface
{
}
