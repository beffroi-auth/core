<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use Beffroi\Core\Domain\Credential\PasswordChangeRefusal;
use RuntimeException;

/**
 * The message is for the audit trail.
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
