<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\PersonalData;

use function sprintf;

/**
 * A field a dump gives away, and why that gives nothing away: the four reasons decision 0011 writes down
 * (`Reason`), and the sentences of a field's own that no category says as well.
 *
 * The reason is a sentence and not a category, because a person reads it: the page for a RSSI
 * (issue #411) says what stays readable and why, and it says it from these lines.
 */
final readonly class NoPersonalDatum implements FieldClassificationInterface
{
    public function __construct(
        public string $reason,
    ) {
    }

    /**
     * A readable field that turns out to be sealed is not a fault of the schema, it is a line that has
     * stopped being true: the field is a sealed one from that day on.
     */
    public function finding(StoredField $field): ?string
    {
        return $field->sealedUnder === null
            ? null
            : sprintf('%s is sealed, and is declared readable ("%s"): declare it sealed under its family.', $field->name, $this->reason);
    }
}
