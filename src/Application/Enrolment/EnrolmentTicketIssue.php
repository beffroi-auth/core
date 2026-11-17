<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Enrolment;

use Beffroi\Core\Application\Ticket\TicketIssuing;
use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Domain\Enrolment\EnrolmentTicket;
use Beffroi\Core\Domain\Event\EnrolmentTicketIssued;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Ticket\IssuedTicket;
use Beffroi\Core\Exception\TicketRefusedException;
use Beffroi\Core\Port\EnrolmentTicketRepositoryInterface;
use DateTimeImmutable;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

final readonly class EnrolmentTicketIssue
{
    public function __construct(
        private EnrolmentTicketRepositoryInterface $tickets,
        private TicketIssuing $issuing,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * @return IssuedTicket<EnrolmentTicket>
     *
     * @throws TicketRefusedException when the person cannot sign in at all
     */
    public function issue(User $user, RequestContext $context): IssuedTicket
    {
        return $this->issueOnce($user, $context, null);
    }

    /**
     * @return IssuedTicket<EnrolmentTicket>
     *
     * @throws TicketRefusedException when the person cannot sign in at all
     */
    public function issueByAnAdministrator(User $user, Subject $administrator, RequestContext $context): IssuedTicket
    {
        return $this->issueOnce($user, $context, $administrator);
    }

    /**
     * @return IssuedTicket<EnrolmentTicket>
     *
     * @throws TicketRefusedException
     */
    private function issueOnce(User $user, RequestContext $context, ?Subject $administrator): IssuedTicket
    {
        $issued = $this->issuing->issue(
            $this->tickets,
            $user,
            static fn (
                Uuid $id,
                User $user,
                PasswordHash $secret,
                DateTimeImmutable $issuedAt,
                DateTimeImmutable $expiresAt,
            ): EnrolmentTicket => new EnrolmentTicket(
                $id,
                $user,
                $secret,
                $issuedAt,
                $expiresAt,
            ),
        );
        $this->events->dispatch(new EnrolmentTicketIssued($issued->ticket, $issued->ticket->createdAt, $context, $administrator));

        return $issued;
    }
}
