<?php

declare(strict_types=1);

namespace Heidi\Core\Exception;

use LogicException;

/**
 * A retiring key still has a JWS it signed possibly alive: retiring it now is a choice, and the caller has
 * to make it explicitly (SigningKeyRotator::retire() with force).
 */
final class RetirementNotDueException extends LogicException implements ExceptionInterface
{
}
