<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Enrolment;

use Beffroi\Core\Application\Ticket\TicketRedeeming;
use Beffroi\Core\Domain\Enrolment\EnrolmentTicket;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\TicketRefusedException;
use Beffroi\Core\Port\EnrolmentTicketRepositoryInterface;
use SensitiveParameter;
use Symfony\Component\Uid\Uuid;

final readonly class EnrolmentTicketRedemption
{
    public function __construct(
        private EnrolmentTicketRepositoryInterface $tickets,
        private TicketRedeeming $redeeming,
    ) {
    }

    /**
     * @throws TicketRefusedException
     */
    public function redeem(Tenant $tenant, Uuid $id, #[SensitiveParameter] string $value): EnrolmentTicket
    {
        return $this->redeeming->redeem($this->tickets, $tenant, $id, $value);
    }

    public function spend(EnrolmentTicket $ticket): void
    {
        $this->redeeming->spend($this->tickets, $ticket);
    }
}
