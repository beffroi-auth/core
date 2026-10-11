<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Group;

use Beffroi\Core\Domain\PersonalData\ClassifiedFieldsInterface;
use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use DateTimeImmutable;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final class Group implements ClassifiedFieldsInterface
{
    public function __construct(
        public private(set) Uuid $id,
        public readonly Tenant $tenant,
        public private(set) string $name,
        public readonly GroupSource $source,
        public readonly DateTimeImmutable $createdAt,
    ) {
        $this->rename($name);
    }

    public function rename(string $name): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('A group name must not be empty.');
        }
        $this->name = $name;
    }

    /**
     * A group names an organisational unit of its tenant, and nothing of it says who is in it.
     *
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            'name' => new NoPersonalDatum('names a group of the tenant, an organisational unit and not a person'),
            'source' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'createdAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'id' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
            'tenant' => new NoPersonalDatum(Reason::THE_TENANT),
        ];
    }
}
