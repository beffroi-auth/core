<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;

/**
 * The message is for the audit trail.
 */
final class CredentialEnrolmentFailedException extends RuntimeException implements ExceptionInterface
{
}
