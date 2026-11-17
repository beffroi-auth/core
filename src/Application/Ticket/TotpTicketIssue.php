<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Ticket;

use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Domain\Event\TotpTicketIssued;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Ticket\IssuedTicket;
use Beffroi\Core\Domain\Ticket\TotpTicket;
use Beffroi\Core\Exception\TicketRefusedException;
use Beffroi\Core\Port\TotpTicketRepositoryInterface;
use DateTimeImmutable;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * The ticket that lets a person enrol an authenticator app in place of the one they hold (decision 0026), refused
 * where no app of theirs would be accepted.
 */
final readonly class TotpTicketIssue
{
    public function __construct(
        private TotpTicketRepositoryInterface $tickets,
        private TicketIssuing $issuing,
        private CredentialTicketRefusal $refusal,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * @return IssuedTicket<TotpTicket>
     *
     * @throws TicketRefusedException when the person cannot sign in at all, or would be refused an authenticator app
     */
    public function issue(User $user, RequestContext $context): IssuedTicket
    {
        return $this->issueOnce($user, $context, null);
    }

    /**
     * @return IssuedTicket<TotpTicket>
     *
     * @throws TicketRefusedException when the person cannot sign in at all, or would be refused an authenticator app
     */
    public function issueByAnAdministrator(User $user, Subject $administrator, RequestContext $context): IssuedTicket
    {
        return $this->issueOnce($user, $context, $administrator);
    }

    /**
     * @return IssuedTicket<TotpTicket>
     *
     * @throws TicketRefusedException
     */
    private function issueOnce(User $user, RequestContext $context, ?Subject $administrator): IssuedTicket
    {
        $this->refusal->assertItWouldOpen($user, 'one-time code', [TotpCredential::method()]);
        $issued = $this->issuing->issue(
            $this->tickets,
            $user,
            static fn (
                Uuid $id,
                User $user,
                PasswordHash $secret,
                DateTimeImmutable $issuedAt,
                DateTimeImmutable $expiresAt,
            ): TotpTicket => new TotpTicket(
                $id,
                $user,
                $secret,
                $issuedAt,
                $expiresAt,
            ),
        );
        $this->events->dispatch(new TotpTicketIssued($issued->ticket, $issued->ticket->createdAt, $context, $administrator));

        return $issued;
    }
}
