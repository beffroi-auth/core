<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\PersonalData;

use function sprintf;

/**
 * A personal datum not sealed yet, named with the issue that seals it; once it is sealed, the walk fails.
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
