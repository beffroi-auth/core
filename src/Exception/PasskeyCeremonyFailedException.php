<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;

/**
 * The message is for the audit trail; the user is told only that the passkey was not accepted.
 */
final class PasskeyCeremonyFailedException extends RuntimeException implements ExceptionInterface
{
}
