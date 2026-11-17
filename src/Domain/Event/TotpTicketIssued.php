<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Domain\Ticket\TotpTicket;
use DateTimeImmutable;

final readonly class TotpTicketIssued implements ConcernsAPersonInterface, NamesItsAuthorInterface
{
    public const string NAME = 'totp_ticket.issued';

    public Tenant $tenant;

    /**
     * @param Subject|null $administrator the administrator of the tenant who issued it, by the subject of their account
     */
    public function __construct(
        public TotpTicket $ticket,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
        public ?Subject $administrator = null,
    ) {
        $this->tenant = $ticket->tenant;
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function subject(): Subject
    {
        return $this->ticket->user->subject;
    }

    public function author(): ?Subject
    {
        return $this->administrator;
    }
}
