<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Journal;

use Beffroi\Core\Domain\PersonalData\ClassifiedFieldsInterface;
use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * The place of an act in the chain of its tenant, as the journal stored it beside the act.
 */
final class JournalLink implements ClassifiedFieldsInterface
{
    public function __construct(
        public private(set) JournalEntry $act,
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
            'act' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
            'tenant' => new NoPersonalDatum(Reason::THE_TENANT),
            'position' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'previous' => new NoPersonalDatum(Reason::HASHED),
            'hash' => new NoPersonalDatum(Reason::HASHED),
        ];
    }
}
