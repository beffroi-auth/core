<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Ticket\Ticket;
use Beffroi\Core\Port\TicketRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @template T of Ticket
 *
 * @implements TicketRepositoryInterface<T>
 */
abstract class InMemoryTicketRepository implements TicketRepositoryInterface
{
    /**
     * @var array<string, T>
     */
    private array $tickets = [];

    public function find(Uuid $id): ?Ticket
    {
        return $this->tickets[$id->toRfc4122()] ?? null;
    }

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

    public function save(Ticket $ticket): void
    {
        $this->tickets[$ticket->id->toRfc4122()] = $ticket;
    }

    public function forget(Ticket $ticket): void
    {
        unset($this->tickets[$ticket->id->toRfc4122()]);
    }
}
