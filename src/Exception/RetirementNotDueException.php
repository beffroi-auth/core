<?php

declare(strict_types=1);

namespace Heidi\Core\Exception;

use LogicException;

/**
 * A JWS the key signed may be alive; retiring it now has to be forced.
 */
final class RetirementNotDueException extends LogicException implements ExceptionInterface
{
}
