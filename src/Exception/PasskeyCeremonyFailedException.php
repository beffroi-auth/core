<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;

/**
 * A WebAuthn ceremony (Level 3 §7.1, §7.2) refused the response of the browser: the message is for the
 * audit trail, the user is told that the passkey was not accepted.
 */
final class PasskeyCeremonyFailedException extends RuntimeException implements ExceptionInterface
{
}
