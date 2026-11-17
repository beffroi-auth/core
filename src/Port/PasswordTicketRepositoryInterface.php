<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Ticket\PasswordTicket;

/**
 * @extends TicketRepositoryInterface<PasswordTicket>
 */
interface PasswordTicketRepositoryInterface extends TicketRepositoryInterface
{
}
