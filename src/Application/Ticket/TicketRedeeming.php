<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Ticket;

use Beffroi\Core\Application\Authentication\DecoyHash;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Domain\Ticket\Ticket;
use Beffroi\Core\Exception\TicketRefusedException;
use Beffroi\Core\Port\ExclusiveSectionsInterface;
use Beffroi\Core\Port\PasswordHasherInterface;
use Beffroi\Core\Port\TicketRepositoryInterface;
use Closure;
use Psr\Clock\ClockInterface;
use SensitiveParameter;
use Symfony\Component\Uid\Uuid;

/**
 * How a ticket of any kind is presented and spent (decision 0026): every refusal costs a hash and says the same
 * sentence, and a ticket is spent once what it opened is written, alone among the redemptions of that ticket.
 */
final readonly class TicketRedeeming
{
    private const string REFUSED = 'This ticket opens nothing.';

    public function __construct(
        private PasswordHasherInterface $hasher,
        private DecoyHash $decoy,
        private ClockInterface $clock,
        private ExclusiveSectionsInterface $sections,
    ) {
    }

    /**
     * @template T of Ticket
     *
     * @param TicketRepositoryInterface<T> $tickets the tickets of the kind
     *
     * @return T
     *
     * @throws TicketRefusedException
     */
    public function redeem(TicketRepositoryInterface $tickets, Tenant $tenant, Uuid $id, #[SensitiveParameter] string $value): Ticket
    {
        $ticket = $tickets->find($id);
        if ($ticket === null || ! $ticket->belongsTo($tenant)) {
            $this->decoy->verify($value);
            throw new TicketRefusedException(self::REFUSED);
        }
        if (! $this->hasher->verify($ticket->secret, $value)) {
            throw new TicketRefusedException(self::REFUSED);
        }
        // After the value on purpose: a ticket that was spent answers like a wrong value.
        if (! $ticket->opens($this->clock->now())) {
            throw new TicketRefusedException(self::REFUSED);
        }

        return $ticket;
    }

    /**
     * Writes what the ticket opens, then spends it, while no other redemption of the ticket runs: of two at once, the
     * second finds it spent and writes nothing. A write that fails leaves the ticket unspent.
     *
     * @template T of Ticket
     * @template W
     *
     * @param TicketRepositoryInterface<T> $tickets the tickets of the kind
     * @param T                            $ticket
     * @param Closure(): W                 $writes  what the ticket opens
     *
     * @param-immediately-invoked-callable $writes
     *
     * @return W
     *
     * @throws TicketRefusedException when another redemption spent the ticket first
     */
    public function spendOnceWritten(TicketRepositoryInterface $tickets, Ticket $ticket, Closure $writes): mixed
    {
        return $this->sections->exclusively('beffroi.ticket.' . $ticket->id->toRfc4122(), function () use ($tickets, $ticket, $writes): mixed {
            if ($tickets->holdsSpent($ticket)) {
                throw new TicketRefusedException(self::REFUSED);
            }
            $written = $writes();
            $ticket->spend($this->clock->now());
            $tickets->save($ticket);

            return $written;
        });
    }
}
