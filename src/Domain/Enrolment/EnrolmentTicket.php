<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Enrolment;

use Beffroi\Core\Domain\Ticket\Ticket;

/**
 * Opens the enrolment of a passkey once, without a session, whether the person holds one already or not.
 */
final class EnrolmentTicket extends Ticket
{
}
