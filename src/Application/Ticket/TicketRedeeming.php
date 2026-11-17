<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Ticket;

use Beffroi\Core\Application\Authentication\DecoyHash;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Domain\Ticket\Ticket;
use Beffroi\Core\Exception\TicketRefusedException;
use Beffroi\Core\Port\PasswordHasherInterface;
use Beffroi\Core\Port\TicketRepositoryInterface;
use Psr\Clock\ClockInterface;
use SensitiveParameter;
use Symfony\Component\Uid\Uuid;

/**
 * How a ticket of any kind is presented and spent (decision 0026): every refusal costs a hash and says the same
 * sentence, and a ticket is spent once what it opened is written.
 */
final readonly class TicketRedeeming
{
    private const string REFUSED = 'This ticket opens nothing.';

    public function __construct(
        private PasswordHasherInterface $hasher,
        private DecoyHash $decoy,
        private ClockInterface $clock,
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
     * @template T of Ticket
     *
     * @param TicketRepositoryInterface<T> $tickets the tickets of the kind
     * @param T                            $ticket
     */
    public function spend(TicketRepositoryInterface $tickets, Ticket $ticket): void
    {
        $ticket->spend($this->clock->now());
        $tickets->save($ticket);
    }
}
