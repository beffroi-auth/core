<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Enrolment\EnrolmentTicket;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * Somebody opened one enrolment for this person without a session (issue #613): an act of their account,
 * because a door was opened in their name and they read it in their own journal. What came of it, a
 * passkey, is the act that follows (`CredentialEnrolled`).
 */
final readonly class EnrolmentTicketIssued implements ConcernsAPersonInterface
{
    /**
     * The name of the act, which is what the journal writes down (issue #454).
     */
    public const string NAME = 'enrolment_ticket.issued';

    public Tenant $tenant;

    public function __construct(
        public EnrolmentTicket $ticket,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
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
}
