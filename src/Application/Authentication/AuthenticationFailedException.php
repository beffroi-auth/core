<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Authentication;

use Heidi\Core\Domain\Event\AuthenticationFailureReason;
use Heidi\Core\Exception\ExceptionInterface;
use RuntimeException;

/**
 * The reason is for the audit trail; the user sees the same message whatever it is.
 */
final class AuthenticationFailedException extends RuntimeException implements ExceptionInterface
{
    public function __construct(
        public readonly AuthenticationFailureReason $reason,
    ) {
        parent::__construct('Authentication failed.');
    }
}
