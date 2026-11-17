<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;

/**
 * Whoever presents a ticket is told one flat sentence, never which reason refused it; whoever issues one is told why
 * none is issued.
 */
final class TicketRefusedException extends RuntimeException implements ExceptionInterface
{
}
