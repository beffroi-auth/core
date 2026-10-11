<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Domain\PersonalData\ClassifiedFieldsInterface;
use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use DateTimeImmutable;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Symfony\Component\Uid\Uuid;

/**
 * A way for a user to prove who they are; a user has N of them.
 */
abstract class Credential implements ClassifiedFieldsInterface
{
    /**
     * Restated from the user (decision 0002).
     */
    public readonly Tenant $tenant;

    public private(set) ?DateTimeImmutable $lastUsedAt = null;

    public function __construct(
        public private(set) Uuid $id,
        public readonly User $user,
        public readonly DateTimeImmutable $createdAt,
    ) {
        $this->tenant = $user->tenant;
    }

    /**
     * What the kind is called, for a console or an audit trail: "password", "passkey". Each kind names
     * itself; nothing reads a class name.
     */
    abstract public function kind(): string;

    /**
     * What the user called it, when the kind lets them name one (a passkey); null otherwise.
     */
    public function label(): ?string
    {
        return null;
    }

    /**
     * Restates when the credential was last used: what a store replays and what an import brings. A
     * login does not call this, it calls the recordUse() of the kind, which holds what a use of that
     * kind means (a sign count, a spent code, a date).
     */
    public function markUsed(DateTimeImmutable $now): void
    {
        $this->lastUsedAt = $now;
    }

    /**
     * What every credential of a person holds, whichever kind it is: the lines the tables of the three kinds share
     * are declared here once, as their mapping is.
     *
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            'createdAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'lastUsedAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'id' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
            'tenant' => new NoPersonalDatum(Reason::THE_TENANT),
            'user' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
        ];
    }
}
