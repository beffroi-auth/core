<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Authentication;

use Heidi\Core\Domain\Event\AuthenticationFailureReason;
use Heidi\Core\Exception\ExceptionInterface;
use RuntimeException;

/**
 * The attempt failed. The reason is for the audit trail and the logs; what a user sees is the same message
 * whatever the reason, so that an attacker learns nothing about which accounts exist.
 */
final class AuthenticationFailedException extends RuntimeException implements ExceptionInterface
{
    public function __construct(
        public readonly AuthenticationFailureReason $reason,
    ) {
        parent::__construct('Authentication failed.');
    }
}
