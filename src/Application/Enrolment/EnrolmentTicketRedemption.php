<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Enrolment;

use Beffroi\Core\Application\Authentication\DecoyHash;
use Beffroi\Core\Domain\Enrolment\EnrolmentTicket;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\EnrolmentTicketRefusedException;
use Beffroi\Core\Port\EnrolmentTicketRepositoryInterface;
use Beffroi\Core\Port\PasswordHasherInterface;
use Psr\Clock\ClockInterface;
use SensitiveParameter;
use Symfony\Component\Uid\Uuid;

final readonly class EnrolmentTicketRedemption
{
    private const string REFUSED = 'This enrolment ticket opens nothing.';

    public function __construct(
        private EnrolmentTicketRepositoryInterface $tickets,
        private PasswordHasherInterface $hasher,
        private DecoyHash $decoy,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws EnrolmentTicketRefusedException
     */
    public function redeem(Tenant $tenant, Uuid $id, #[SensitiveParameter] string $value): EnrolmentTicket
    {
        $ticket = $this->tickets->find($id);
        if ($ticket === null || ! $ticket->belongsTo($tenant)) {
            $this->decoy->verify($value);

            throw new EnrolmentTicketRefusedException(self::REFUSED);
        }
        if (! $this->hasher->verify($ticket->secret, $value)) {
            throw new EnrolmentTicketRefusedException(self::REFUSED);
        }
        // After the value on purpose: a ticket that was spent answers like a wrong value.
        if (! $ticket->opensAnEnrolment($this->clock->now())) {
            throw new EnrolmentTicketRefusedException(self::REFUSED);
        }

        return $ticket;
    }

    public function spend(EnrolmentTicket $ticket): void
    {
        $ticket->spend($this->clock->now());
        $this->tickets->save($ticket);
    }
}
