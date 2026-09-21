<?php

declare(strict_types=1);

namespace Heidi\Core\Exception;

use InvalidArgumentException;

/**
 * Nothing wired signs under that name: a client, a stored key or an operator named an algorithm the
 * provider does not sign with.
 */
final class UnsupportedAlgorithmException extends InvalidArgumentException implements ExceptionInterface
{
}
