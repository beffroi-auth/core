<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;

/**
 * A lookup that fails is this exception and never a count of zero.
 */
final class BreachLookupFailedException extends RuntimeException implements ExceptionInterface
{
}
