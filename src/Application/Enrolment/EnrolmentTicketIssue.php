<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Enrolment;

use Beffroi\Core\Domain\Encoding\Base64Url;
use Beffroi\Core\Domain\Enrolment\EnrolmentTicket;
use Beffroi\Core\Domain\Enrolment\IssuedEnrolmentTicket;
use Beffroi\Core\Domain\Event\EnrolmentTicketIssued;
use Beffroi\Core\Domain\Identity\Subject;
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

final readonly class EnrolmentTicketIssue
{
    public const int LENGTH = 32;

    /**
     * Covers a weekend between the day a ticket is issued and the day somebody starts.
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
     * @throws EnrolmentTicketRefusedException when the person cannot sign in at all
     */
    public function issue(User $user, RequestContext $context): IssuedEnrolmentTicket
    {
        return $this->issueOnce($user, $context, null);
    }

    /**
     * @throws EnrolmentTicketRefusedException when the person cannot sign in at all
     */
    public function issueByAnAdministrator(User $user, Subject $administrator, RequestContext $context): IssuedEnrolmentTicket
    {
        return $this->issueOnce($user, $context, $administrator);
    }

    /**
     * @throws EnrolmentTicketRefusedException
     */
    private function issueOnce(User $user, RequestContext $context, ?Subject $administrator): IssuedEnrolmentTicket
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
        $this->events->dispatch(new EnrolmentTicketIssued($ticket, $now, $context, $administrator));

        return new IssuedEnrolmentTicket($ticket, $value);
    }
}
