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

abstract class Credential implements ClassifiedFieldsInterface
{
    public readonly Tenant $tenant;

    public private(set) ?DateTimeImmutable $lastUsedAt = null;

    public function __construct(
        public private(set) Uuid $id,
        public readonly User $user,
        public readonly DateTimeImmutable $createdAt,
    ) {
        $this->tenant = $user->tenant;
    }

    abstract public function kind(): string;

    public function label(): ?string
    {
        return null;
    }

    /**
     * For a store or an import: a login calls recordUse(), which also moves a sign count or a spent code.
     */
    public function markUsed(DateTimeImmutable $now): void
    {
        $this->lastUsedAt = $now;
    }

    /**
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
