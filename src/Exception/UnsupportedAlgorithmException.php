<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use InvalidArgumentException;

final class UnsupportedAlgorithmException extends InvalidArgumentException implements ExceptionInterface
{
}
