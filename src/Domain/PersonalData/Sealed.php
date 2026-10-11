<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\PersonalData;

use function sprintf;

/**
 * A personal datum, sealed at rest under the data key of its tenant and of its family (decisions 0011 and
 * 0018). The family is the scope that key is shared over, so two fields of one family share one key and
 * one unwrap.
 *
 * **A field seals under the family of its own table**, and that is the one thing this line checks beyond
 * the sealing itself: a field sealed under another family is a value written into the scope of a table
 * that is not its own, which no destruction of its tenant's keys for that table would reach. Two shapes
 * of a mapping can do it, a family typed wrong and an embeddable, whose fields land on every entity that
 * embeds it; neither is visible in the mapping that would be wrong, so the walk of the schema reads it
 * here.
 */
final readonly class Sealed implements FieldClassificationInterface
{
    public function __construct(
        public string $family,
    ) {
    }

    public function finding(StoredField $field): ?string
    {
        if ($field->sealedUnder === null) {
            return sprintf(
                '%s is declared sealed under the "%s" family and what stores it does not seal it.',
                $field->name,
                $this->family,
            );
        }

        if ($this->family !== $field->family) {
            return sprintf(
                '%s is declared sealed under the "%s" family, and a field seals under its own table, so under "%s".',
                $field->name,
                $this->family,
                $field->family,
            );
        }

        if ($field->sealedUnder !== $this->family) {
            return sprintf(
                '%s is declared sealed under the "%s" family and is sealed under "%s", which is another family: a field of an embeddable several entities embed cannot carry the type of one of their tables.',
                $field->name,
                $this->family,
                $field->sealedUnder,
            );
        }

        return null;
    }
}
