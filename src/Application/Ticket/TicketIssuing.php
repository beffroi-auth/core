<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Ticket;

use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Domain\Encoding\Base64Url;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Ticket\IssuedTicket;
use Beffroi\Core\Domain\Ticket\Ticket;
use Beffroi\Core\Exception\TicketRefusedException;
use Beffroi\Core\Port\PasswordHasherInterface;
use Beffroi\Core\Port\RandomnessInterface;
use Beffroi\Core\Port\TicketRepositoryInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use Closure;
use DateInterval;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use function sprintf;
use Symfony\Component\Uid\Uuid;

/**
 * How a ticket of any kind is issued (decision 0026): a person who cannot sign in at all gets none, the tickets of the
 * kind they had not spent go, and the value the link carries is drawn and kept as a hash only.
 */
final readonly class TicketIssuing
{
    public const int LENGTH = 32;

    /**
     * Covers a weekend between the day a ticket is issued and the day somebody spends it.
     */
    public const string LIFETIME = 'PT72H';

    public function __construct(
        private UuidGeneratorInterface $uuids,
        private RandomnessInterface $randomness,
        private PasswordHasherInterface $hasher,
        private ClockInterface $clock,
        private DateInterval $lifetime = new DateInterval(self::LIFETIME),
    ) {
    }

    /**
     * @template T of Ticket
     *
     * @param TicketRepositoryInterface<T>                                                 $tickets the tickets of the kind
     * @param Closure(Uuid, User, PasswordHash, DateTimeImmutable, DateTimeImmutable): T $draw    the ticket of the kind, from its identifier, person, hash and instants of issue and expiry
     *
     * @return IssuedTicket<T>
     *
     * @throws TicketRefusedException when the person cannot sign in at all
     */
    public function issue(TicketRepositoryInterface $tickets, User $user, Closure $draw): IssuedTicket
    {
        if (! $user->isEnabled()) {
            throw new TicketRefusedException(sprintf('The account of "%s" is disabled; a ticket would open nothing.', $user->username));
        }
        foreach ($tickets->unspentOf($user) as $unspent) {
            $tickets->forget($unspent);
        }
        $now = $this->clock->now();
        $value = Base64Url::encode($this->randomness->bytes(self::LENGTH));
        $ticket = $draw($this->uuids->timeOrdered(), $user, $this->hasher->hash($value), $now, $now->add($this->lifetime));
        $tickets->save($ticket);

        return new IssuedTicket($ticket, $value);
    }
}
