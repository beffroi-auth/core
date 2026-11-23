<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Ticket\Ticket;
use Symfony\Component\Uid\Uuid;

/**
 * The tickets of one kind, found by identifier only: the row holds a salted hash of the value.
 *
 * @template T of Ticket
 */
interface TicketRepositoryInterface
{
    /**
     * @return T|null
     */
    public function find(Uuid $id): ?Ticket;

    /**
     * Expired ones included.
     *
     * @return list<T>
     */
    public function unspentOf(User $user): array;

    /**
     * Read in the store, past the copy the caller holds: another process may have spent it since. A ticket the store
     * no longer holds is spent.
     *
     * @param T $ticket
     */
    public function holdsSpent(Ticket $ticket): bool;

    /**
     * @param T $ticket
     */
    public function save(Ticket $ticket): void;

    /**
     * A ticket already gone is forgotten again without complaint.
     *
     * @param T $ticket
     */
    public function forget(Ticket $ticket): void;
}
