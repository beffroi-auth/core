<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Ticket\PasswordTicket;
use Beffroi\Core\Port\PasswordTicketRepositoryInterface;

/**
 * @extends InMemoryTicketRepository<PasswordTicket>
 */
final class InMemoryPasswordTicketRepository extends InMemoryTicketRepository implements PasswordTicketRepositoryInterface
{
}
