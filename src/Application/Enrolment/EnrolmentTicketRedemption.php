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

/**
 * What a ticket presented opens, and the whole order of reading it (issue #613): the ticket is found by
 * the identifier the URL carries, the value is verified against the hash the row holds, and what the
 * ticket still is comes after, as a disabled account comes after the password (`PasswordVerification`,
 * decision 0024).
 *
 * **Every refusal costs one hash and says the same thing** (decision 0006): an identifier nothing answers
 * pays a decoy, so the time does not say whether a ticket exists; a spent ticket, one out of time and one
 * of a person who cannot sign in are refused once the value was verified, so the time does not say which.
 *
 * **It opens an enrolment and nothing else.** What the caller does with the person it names is pose a
 * credential for them (`PasskeyEnrolment`); the ticket is spent once that credential is in the rows, so a
 * ceremony that failed leaves the ticket usable.
 */
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
        // after the value on purpose: a ticket that was spent answers like a wrong value
        if (! $ticket->opensAnEnrolment($this->clock->now())) {
            throw new EnrolmentTicketRefusedException(self::REFUSED);
        }

        return $ticket;
    }

    /**
     * Spent once what it opened is in the rows, and never before: a ceremony that failed leaves the person
     * their link.
     */
    public function spend(EnrolmentTicket $ticket): void
    {
        $ticket->spend($this->clock->now());
        $this->tickets->save($ticket);
    }
}
