<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use LogicException;

final class RetirementNotDueException extends LogicException implements ExceptionInterface
{
}
