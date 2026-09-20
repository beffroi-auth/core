<?php

declare(strict_types=1);

namespace Heidi\Core\Exception;

use RuntimeException;

/**
 * The KMS of the tenant did not answer or refused a call it should have served: unreachable, in error,
 * or without the master key of the tenant. An outage, not a wrong input: the request is served again
 * once the KMS is back, nothing of the user was refused. What the KMS refuses as an envelope, invalid,
 * tampered or foreign to this use or this tenant, stays a RuntimeException, a bug or an attack.
 */
final class KmsUnavailableException extends RuntimeException implements ExceptionInterface
{
}
