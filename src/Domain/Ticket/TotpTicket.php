<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Ticket;

/**
 * Opens the enrolment of an authenticator app once, in place of the one the person holds, and without a session.
 */
final class TotpTicket extends Ticket
{
}
