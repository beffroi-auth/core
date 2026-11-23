<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;

/**
 * A confirmation that is not sent: the person, or the address, had every confirmation its budget allows.
 */
final class AddressConfirmationWithheldException extends RuntimeException implements ExceptionInterface
{
}
