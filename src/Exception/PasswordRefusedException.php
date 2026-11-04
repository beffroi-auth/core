<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use Beffroi\Core\Domain\Credential\PasswordRefusal;
use InvalidArgumentException as InvalidArgument;
use Throwable;

final class PasswordRefusedException extends InvalidArgument implements ExceptionInterface
{
    public function __construct(
        public readonly PasswordRefusal $refusal,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
