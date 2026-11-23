<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Chain;

use Beffroi\Core\Domain\PersonalData\ClassifiedFieldsInterface;
use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * The last link of a chain of a tenant, which outlives the rows the retention forgets, and the row a write locks so
 * that two writers never take the same position.
 */
final class ChainHead implements ClassifiedFieldsInterface
{
    public function __construct(
        public private(set) Tenant $tenant,
        public private(set) string $chain,
        public private(set) int $position,
        public private(set) string $hash,
    ) {
    }

    /**
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            'tenant' => new NoPersonalDatum(Reason::THE_TENANT),
            'chain' => new NoPersonalDatum('the name of a record of the provider, which names no person'),
            'position' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'hash' => new NoPersonalDatum(Reason::HASHED),
        ];
    }
}
