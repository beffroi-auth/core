<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

use Beffroi\Core\Domain\Algorithm\NamedAlgorithmInterface;

/**
 * RFC 7518 §5
 */
interface ContentEncryptionAlgorithmInterface extends NamedAlgorithmInterface
{
}
