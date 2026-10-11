<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Enrolment\EnrolmentTicket;
use Beffroi\Core\Domain\Identity\User;
use Symfony\Component\Uid\Uuid;

/**
 * The enrolment tickets of this provider (issue #613). The lookup is by identifier, which is what the URL
 * carries beside the value: the row holds a hash of the value, salted, so nothing can be found by it.
 */
interface EnrolmentTicketRepositoryInterface
{
    public function find(Uuid $id): ?EnrolmentTicket;

    /**
     * The tickets of that person that were never spent, expired or not: what issuing a new one takes
     * away, so that one person has one usable ticket at a time.
     *
     * @return list<EnrolmentTicket>
     */
    public function unspentOf(User $user): array;

    public function save(EnrolmentTicket $ticket): void;

    /**
     * The row goes. A ticket already gone is forgotten again without complaint.
     */
    public function forget(EnrolmentTicket $ticket): void;
}
