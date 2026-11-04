<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;

/**
 * Whoever presents a ticket is told one flat sentence, never which reason refused it.
 */
final class EnrolmentTicketRefusedException extends RuntimeException implements ExceptionInterface
{
}
