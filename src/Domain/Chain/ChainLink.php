<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Chain;

use Beffroi\Core\Domain\PersonalData\ClassifiedFieldsInterface;
use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * The place of a row in a chain of its tenant, stored beside the row by the record that wrote it.
 */
final class ChainLink implements ClassifiedFieldsInterface
{
    public function __construct(
        public private(set) string $chain,
        public private(set) string $row,
        public private(set) Tenant $tenant,
        public private(set) int $position,
        public private(set) string $previous,
        public private(set) string $hash,
    ) {
    }

    /**
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            'chain' => new NoPersonalDatum('the name of a record of the provider, which names no person'),
            'row' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
            'tenant' => new NoPersonalDatum(Reason::THE_TENANT),
            'position' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'previous' => new NoPersonalDatum(Reason::HASHED),
            'hash' => new NoPersonalDatum(Reason::HASHED),
        ];
    }
}
