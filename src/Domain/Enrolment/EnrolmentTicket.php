<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Enrolment;

use Beffroi\Core\Domain\PersonalData\ClassifiedFieldsInterface;
use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * Opens one enrolment once without a session; it is no credential and proves no login.
 */
final class EnrolmentTicket implements ClassifiedFieldsInterface
{
    public readonly Tenant $tenant;

    public private(set) ?DateTimeImmutable $spentAt = null;

    public function __construct(
        /**
         * Not readonly: a mapper building lazy references assigns it twice.
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

    public function opensAnEnrolment(DateTimeImmutable $now): bool
    {
        return ! $this->isSpent() && ! $this->isExpired($now) && $this->user->isEnabled();
    }

    /**
     * A second spending is refused, not ignored: it would be a second credential from one ticket.
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

    /**
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            'secret' => new NoPersonalDatum(Reason::HASHED),
            'createdAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'expiresAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'spentAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'id' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
            'tenant' => new NoPersonalDatum(Reason::THE_TENANT),
            'user' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
        ];
    }
}
