<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;

/**
 * A ticket was not issued, or the one presented opens nothing (issue #613). Whoever asks for one is told
 * why; whoever presents one is told the one flat sentence and nothing of which of the reasons it was
 * (decision 0006), an unknown identifier, a wrong value, a ticket spent, out of time or of a person who
 * cannot sign in being the same answer.
 */
final class EnrolmentTicketRefusedException extends RuntimeException implements ExceptionInterface
{
}
