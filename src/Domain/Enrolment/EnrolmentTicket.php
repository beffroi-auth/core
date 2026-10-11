<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Enrolment;

use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * What lets one person reach one enrolment once, without a session (issue #613): a value drawn for them,
 * dated, spendable once, handed over by whoever issued it. It is how somebody poses a first passkey on a
 * tenant that signs its users in by passkey and holds no password of theirs to sign in with (decision
 * 0025).
 *
 * **It is not a credential and it proves no login.** It opens an enrolment, and the session that follows
 * comes from the passkey that was just posed. A ticket that opened a session would be a login by
 * possession of whatever carried it, which is another thing with another decision behind it.
 *
 * **What the row holds is a hash and never the value**, as a backup code does (`BackupCode`): the URL
 * carries the identifier and the value, the row holds the identifier and the hash, so a store read by
 * somebody else opens nothing. The identifier is what the lookup is made by, the value being salted.
 */
final class EnrolmentTicket
{
    public readonly Tenant $tenant;

    public private(set) ?DateTimeImmutable $spentAt = null;

    public function __construct(
        /**
         * On every entity the identifier is private(set), not readonly: a mapper building lazy references
         * assigns it twice.
         */
        public private(set) Uuid $id,
        public readonly User $user,
        public readonly PasswordHash $secret,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $expiresAt,
    ) {
        if ($expiresAt <= $createdAt) {
            throw new InvalidArgumentException('An enrolment ticket expires after it was issued.');
        }
        $this->tenant = $user->tenant;
    }

    public function belongsTo(Tenant $tenant): bool
    {
        return $this->tenant->equals($tenant);
    }

    public function isSpent(): bool
    {
        return $this->spentAt !== null;
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $now >= $this->expiresAt;
    }

    /**
     * Whether it still opens the enrolment it was issued for: it was not spent, it is in time, and the
     * person it names can still sign in at all.
     */
    public function opensAnEnrolment(DateTimeImmutable $now): bool
    {
        return ! $this->isSpent() && ! $this->isExpired($now) && $this->user->isEnabled();
    }

    /**
     * Spent once the enrolment it opened is in the rows. A second spending is refused rather than
     * ignored: whoever spends one has just written a credential, and doing it twice would be two
     * credentials from one ticket.
     *
     * @throws InvalidArgumentException
     */
    public function spend(DateTimeImmutable $now): void
    {
        if ($this->isSpent()) {
            throw new InvalidArgumentException('This enrolment ticket is spent already.');
        }
        $this->spentAt = $now;
    }
}
