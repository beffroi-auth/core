<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Enrolment;

use Beffroi\Core\Domain\Encoding\Base64Url;
use Beffroi\Core\Domain\Enrolment\EnrolmentTicket;
use Beffroi\Core\Domain\Enrolment\IssuedEnrolmentTicket;
use Beffroi\Core\Domain\Event\EnrolmentTicketIssued;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Exception\EnrolmentTicketRefusedException;
use Beffroi\Core\Port\EnrolmentTicketRepositoryInterface;
use Beffroi\Core\Port\PasswordHasherInterface;
use Beffroi\Core\Port\RandomnessInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use DateInterval;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function sprintf;

/**
 * Issues the ticket that lets one person reach one enrolment without a session (issue #613), for whoever
 * administers the tenant: a command today, a screen of the console tomorrow, the same use case for both
 * (architecture §5.5).
 *
 * **The value is drawn here and read nowhere again**: 256 bits of the randomness of the deployment,
 * written in base64url so that it travels in a URL, hashed by the hasher of this provider as a backup
 * code is, and handed back once with the ticket. The row holds the hash.
 *
 * **One person has one usable ticket at a time**: issuing one forgets the unspent ones of that person, so
 * a link handed over twice is one link, and a link forgotten in a mailbox stops opening anything the day
 * another is issued.
 */
final readonly class EnrolmentTicketIssue
{
    /**
     * How many bytes are drawn for the value of a ticket, before they are written in base64url.
     */
    public const int LENGTH = 32;

    /**
     * How long a ticket opens its enrolment. Seventy-two hours, which covers a weekend between the day an
     * administrator issues one and the day somebody starts; what is not used in three days is issued
     * again rather than left open.
     */
    public const string LIFETIME = 'PT72H';

    public function __construct(
        private EnrolmentTicketRepositoryInterface $tickets,
        private UuidGeneratorInterface $uuids,
        private RandomnessInterface $randomness,
        private PasswordHasherInterface $hasher,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
        private DateInterval $lifetime = new DateInterval(self::LIFETIME),
    ) {
    }

    /**
     * @throws EnrolmentTicketRefusedException when the person cannot sign in at all: a disabled account is
     *                                         not a door to open
     */
    public function issue(User $user, RequestContext $context): IssuedEnrolmentTicket
    {
        if (! $user->isEnabled()) {
            throw new EnrolmentTicketRefusedException(sprintf('The account of "%s" is disabled; a ticket would open nothing.', $user->username));
        }
        foreach ($this->tickets->unspentOf($user) as $unspent) {
            $this->tickets->forget($unspent);
        }
        $now = $this->clock->now();
        $value = Base64Url::encode($this->randomness->bytes(self::LENGTH));
        $ticket = new EnrolmentTicket(
            $this->uuids->timeOrdered(),
            $user,
            $this->hasher->hash($value),
            $now,
            $now->add($this->lifetime),
        );
        $this->tickets->save($ticket);
        $this->events->dispatch(new EnrolmentTicketIssued($ticket, $now, $context));

        return new IssuedEnrolmentTicket($ticket, $value);
    }
}
