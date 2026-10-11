<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Enrolment\EnrolmentTicket;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\EnrolmentTicketRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class InMemoryEnrolmentTicketRepository implements EnrolmentTicketRepositoryInterface
{
    /**
     * @var array<string, EnrolmentTicket>
     */
    private array $tickets = [];

    public function find(Uuid $id): ?EnrolmentTicket
    {
        return $this->tickets[$id->toRfc4122()] ?? null;
    }

    /**
     * @return list<EnrolmentTicket>
     */
    public function unspentOf(User $user): array
    {
        $found = [];
        foreach ($this->tickets as $ticket) {
            if (! $ticket->isSpent() && $ticket->user->id->equals($user->id)) {
                $found[] = $ticket;
            }
        }

        return $found;
    }

    public function save(EnrolmentTicket $ticket): void
    {
        $this->tickets[$ticket->id->toRfc4122()] = $ticket;
    }

    public function forget(EnrolmentTicket $ticket): void
    {
        unset($this->tickets[$ticket->id->toRfc4122()]);
    }
}
