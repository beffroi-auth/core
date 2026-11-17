<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Ticket\TotpTicket;
use Beffroi\Core\Port\TotpTicketRepositoryInterface;

/**
 * @extends InMemoryTicketRepository<TotpTicket>
 */
final class InMemoryTotpTicketRepository extends InMemoryTicketRepository implements TotpTicketRepositoryInterface
{
}
