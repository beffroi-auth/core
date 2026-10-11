<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Group;

use Beffroi\Core\Domain\PersonalData\ClassifiedFieldsInterface;
use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use DateTimeImmutable;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final class GroupMembership implements ClassifiedFieldsInterface
{
    /**
     * Restated from the group (decision 0002).
     */
    public readonly Tenant $tenant;

    public function __construct(
        public private(set) Uuid $id,
        public readonly Group $group,
        public readonly User $user,
        public readonly GroupSource $source,
        public readonly DateTimeImmutable $addedAt,
    ) {
        if (! $user->belongsTo($group->tenant)) {
            throw new InvalidArgumentException('A user can only belong to a group of their own tenant.');
        }
        $this->tenant = $group->tenant;
    }

    /**
     * A membership is three identifiers and where it came from, and it names nobody without the tables it joins.
     *
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            'source' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'addedAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'id' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
            'tenant' => new NoPersonalDatum(Reason::THE_TENANT),
            'group' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
            'user' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
        ];
    }
}
