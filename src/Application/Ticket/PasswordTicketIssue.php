<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Ticket;

use Beffroi\Core\Application\Identity\FederatedDomains;
use Beffroi\Core\Domain\Credential\PasswordCredential;
use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Domain\Event\PasswordTicketIssued;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Ticket\IssuedTicket;
use Beffroi\Core\Domain\Ticket\PasswordTicket;
use Beffroi\Core\Exception\TicketRefusedException;
use Beffroi\Core\Port\PasswordTicketRepositoryInterface;
use DateTimeImmutable;
use Psr\EventDispatcher\EventDispatcherInterface;
use function sprintf;
use Symfony\Component\Uid\Uuid;

/**
 * The ticket that lets a person pose a new password without the one in use (decision 0026), refused where no password
 * of theirs would be accepted.
 */
final readonly class PasswordTicketIssue
{
    public function __construct(
        private PasswordTicketRepositoryInterface $tickets,
        private TicketIssuing $issuing,
        private FederatedDomains $federated,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * @return IssuedTicket<PasswordTicket>
     *
     * @throws TicketRefusedException when the person cannot sign in at all, or would be refused a password
     */
    public function issue(User $user, RequestContext $context): IssuedTicket
    {
        return $this->issueOnce($user, $context, null);
    }

    /**
     * @return IssuedTicket<PasswordTicket>
     *
     * @throws TicketRefusedException when the person cannot sign in at all, or would be refused a password
     */
    public function issueByAnAdministrator(User $user, Subject $administrator, RequestContext $context): IssuedTicket
    {
        return $this->issueOnce($user, $context, $administrator);
    }

    /**
     * @return IssuedTicket<PasswordTicket>
     *
     * @throws TicketRefusedException
     */
    private function issueOnce(User $user, RequestContext $context, ?Subject $administrator): IssuedTicket
    {
        if (! $user->tenant->loginFloor->admits([PasswordCredential::method()])) {
            throw new TicketRefusedException('The tenant signs its users in without a password; a password ticket would open nothing.');
        }
        if (! $this->federated->admitsLocalCredentials($user)) {
            throw new TicketRefusedException(sprintf('"%s" is of an e-mail domain the tenant federates, which holds no password of theirs.', $user->username));
        }
        $issued = $this->issuing->issue(
            $this->tickets,
            $user,
            static fn (
                Uuid $id,
                User $user,
                PasswordHash $secret,
                DateTimeImmutable $issuedAt,
                DateTimeImmutable $expiresAt,
            ): PasswordTicket => new PasswordTicket(
                $id,
                $user,
                $secret,
                $issuedAt,
                $expiresAt,
            ),
        );
        $this->events->dispatch(new PasswordTicketIssued($issued->ticket, $issued->ticket->createdAt, $context, $administrator));

        return $issued;
    }
}
