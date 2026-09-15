<?php

declare(strict_types=1);

namespace Heidi\Core\Exception;

use RuntimeException;

final class KeyNotFoundException extends RuntimeException implements ExceptionInterface
{
}
