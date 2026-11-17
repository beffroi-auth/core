<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Ticket\TotpTicket;

/**
 * @extends TicketRepositoryInterface<TotpTicket>
 */
interface TotpTicketRepositoryInterface extends TicketRepositoryInterface
{
}
