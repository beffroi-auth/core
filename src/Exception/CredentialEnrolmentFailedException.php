<?php

declare(strict_types=1);

namespace Heidi\Core\Exception;

use RuntimeException;

/**
 * An enrolment did not end in a credential: the answer of the user was refused, or the credential exists
 * already. The message is for the audit trail; the cause, when a ceremony refused, is the previous exception.
 */
final class CredentialEnrolmentFailedException extends RuntimeException implements ExceptionInterface
{
}
