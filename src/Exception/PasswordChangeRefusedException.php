<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use Beffroi\Core\Domain\Credential\PasswordChangeRefusal;
use RuntimeException;

/**
 * The password of a person was not replaced, and the reason is on the exception because the page that
 * offers the change says which of them it was. The message is for the audit trail.
 */
final class PasswordChangeRefusedException extends RuntimeException implements ExceptionInterface
{
    public function __construct(
        public readonly PasswordChangeRefusal $refusal,
        string $message,
    ) {
        parent::__construct($message);
    }
}
