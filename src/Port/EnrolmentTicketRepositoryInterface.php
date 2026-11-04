<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Enrolment\EnrolmentTicket;
use Beffroi\Core\Domain\Identity\User;
use Symfony\Component\Uid\Uuid;

/**
 * Found by identifier only: the row holds a salted hash of the value.
 */
interface EnrolmentTicketRepositoryInterface
{
    public function find(Uuid $id): ?EnrolmentTicket;

    /**
     * Expired ones included.
     *
     * @return list<EnrolmentTicket>
     */
    public function unspentOf(User $user): array;

    public function save(EnrolmentTicket $ticket): void;

    /**
     * A ticket already gone is forgotten again without complaint.
     */
    public function forget(EnrolmentTicket $ticket): void;
}
