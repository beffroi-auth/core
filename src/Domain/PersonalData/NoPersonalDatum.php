<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\PersonalData;

use function sprintf;

final readonly class NoPersonalDatum implements FieldClassificationInterface
{
    public function __construct(
        public string $reason,
    ) {
    }

    /**
     * A sealed field is a finding too: the line has stopped being true.
     */
    public function finding(StoredField $field): ?string
    {
        return $field->sealedUnder === null
            ? null
            : sprintf('%s is sealed, and is declared readable ("%s"): declare it sealed under its family.', $field->name, $this->reason);
    }
}
