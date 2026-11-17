<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Enrolment\EnrolmentTicket;
use Beffroi\Core\Port\EnrolmentTicketRepositoryInterface;

/**
 * @extends InMemoryTicketRepository<EnrolmentTicket>
 */
final class InMemoryEnrolmentTicketRepository extends InMemoryTicketRepository implements EnrolmentTicketRepositoryInterface
{
}
