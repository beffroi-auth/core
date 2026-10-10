<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use Beffroi\Core\Domain\Credential\PasswordRefusal;
use InvalidArgumentException as InvalidArgument;
use Throwable;

/**
 * The string posed as a password was refused, and the rule that pronounced it is on the exception because
 * the page and the console that posed it say which one it was. The message is one flat sentence, which is
 * what a terminal prints.
 *
 * It is an invalid argument and says so by what it extends: a password that does not pass is an argument
 * the value object refuses, and a caller that catches nothing finer keeps catching it.
 */
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
