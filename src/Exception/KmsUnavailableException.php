<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;

/**
 * An outage; an envelope the KMS refuses as invalid, tampered or foreign stays a RuntimeException.
 */
final class KmsUnavailableException extends RuntimeException implements ExceptionInterface
{
}
