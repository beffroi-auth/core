<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\PersonalData;

use function sprintf;

/**
 * A personal datum that is not sealed yet, named with the issue that seals it, on the model of the
 * "known" list of CONFORMANCE_PLANS: the line says what is owed and who owes it, and the day the field is
 * found sealed the walk of the schema fails, so the line is deleted rather than carried.
 */
final readonly class Owed implements FieldClassificationInterface
{
    public function __construct(
        public string $issue,
        public string $reason,
    ) {
    }

    public function finding(StoredField $field): ?string
    {
        return $field->sealedUnder === null
            ? null
            : sprintf('%s is sealed and %s still owes it ("%s"): delete its line and declare it sealed.', $field->name, $this->issue, $this->reason);
    }
}
