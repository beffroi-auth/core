<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;

final class AuthenticatorListRefusedException extends RuntimeException implements ExceptionInterface
{
}
